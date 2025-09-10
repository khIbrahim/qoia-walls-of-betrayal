<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\Events\Combat\PlayerTaggedEvent;
use fenomeno\WallsOfBetrayal\Events\Combat\PlayerUntaggedEvent;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Task\CombatTask;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\World;
use Symfony\Component\Filesystem\Path;
use Throwable;

final class CombatManager
{
    private Config $config;

    private const CONFIG_FILE           = "combat.yml";
    private const DEFAULT_COMBAT_TIME   = 20;
    private const DEFAULT_BANNED_CMDS   = ['kingdom spawn', 'lobby', 'tp'];
    private const DEFAULT_BANNED_WORLDS = [];
    private const DEFAULT_KILL_ON_DC    = false;
    private const DEFAULT_NOTICE_PLAYER = true;

    /**
     * [
     *   playerName => [
     *     'expireTime' => int,
     *     'opponents' => [
     *       opponentName => int,
     *     ]
     *   ]
     * ]
     */
    private array $combatData = [];

    private array $bannedCommands;
    private int   $combatTime;
    private bool  $killOnDisconnect;
    private bool  $noticePlayer;
    private array $bannedWorlds;

    public function __construct(private readonly Main $main)
    {
        $this->main->saveResource(self::CONFIG_FILE);
        $filePath = Path::join($main->getDataFolder(), self::CONFIG_FILE);
        $this->config = new Config($filePath, Config::YAML);

        $this->bannedCommands   = (array) $this->config->getNested('settings.banned-commands', self::DEFAULT_BANNED_CMDS);
        $this->combatTime       = (int) $this->config->getNested('settings.combat-time', self::DEFAULT_COMBAT_TIME);
        $this->killOnDisconnect = (bool) $this->config->getNested('settings.kill-on-disconnect', self::DEFAULT_KILL_ON_DC);
        $this->noticePlayer     = (bool) $this->config->getNested('settings.noticePlayer', self::DEFAULT_NOTICE_PLAYER);
        $this->bannedWorlds     = (array) $this->config->getNested('settings.banned-worlds', self::DEFAULT_BANNED_WORLDS);

        $this->main->getScheduler()->scheduleRepeatingTask(new CombatTask($this->main), 20);
    }

    public function tagWithOpponent(Player $player, ?Player $opponent = null): void
    {
        if ($player->hasPermission(PermissionIds::BYPASS_COMBAT_TAG) || $this->isBannedWorld($player->getWorld())) {
            return;
        }

        $playerName   = strtolower($player->getName());
        $currentTime  = time();

        $event = new PlayerTaggedEvent($player, $opponent, $this->combatTime);
        $event->call();
        $expireTime = $currentTime + $this->combatTime;

        if (! isset($this->combatData[$playerName])) {
            $this->combatData[$playerName] = [
                'expireTime' => $expireTime,
                'opponents'  => []
            ];

            if ($this->noticePlayer) {
                MessagesUtils::sendTo($player, MessagesIds::STARTED_COMBAT, [
                    ExtraTags::OPPONENT => $opponent?->getName() ?? 'Unknown',
                    ExtraTags::TIME     => $this->combatTime
                ]);
                $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
                    soundName: 'mob.enderdragon.growl',
                    x: $player->getPosition()->getX(),
                    y: $player->getPosition()->getY(),
                    z: $player->getPosition()->getZ(),
                    volume: 1.0,
                    pitch: 1.0
                ));
            }
        } else {
            $this->combatData[$playerName]['expireTime'] = max($this->combatData[$playerName]['expireTime'], $expireTime);
        }

        if ($opponent !== null) {
            $opponentName = strtolower($opponent->getName());
            $this->combatData[$playerName]['opponents'][$opponentName] = $currentTime;
        }
    }

    public function untag(Player $player, bool $untagOpponents = false): void
    {
        $playerName = strtolower($player->getName());

        if (isset($this->combatData[$playerName])) {
            $event = new PlayerUntaggedEvent($player);
            $event->call();

            if (isset($this->combatData[$playerName]['opponents']) && $untagOpponents) {
                $opponents = array_keys($this->combatData[$playerName]['opponents']);
                foreach ($opponents as $opponentName) {
                    Await::g2c(
                        Await::promise(function ($resolve, $reject) use ($opponentName) {
                            try {
                                $opponent = $this->main->getServer()->getPlayerExact($opponentName);
                                if ($opponent instanceof Player) {
                                    $this->untag($opponent);
                                }
                                $resolve(true);
                            } catch (Throwable $e) {
                                $reject($e);
                            }
                        }),
                        function (bool $success){},
                        fn(Throwable $e) => Utils::onFailure($e, $player, 'removing opponent tag of ' . $opponentName)
                    );
                }
            }

            unset($this->combatData[$playerName]);

            if ($this->noticePlayer) {
                MessagesUtils::sendTo($player, MessagesIds::PLAYER_COMBAT_UNTAGGED);
                $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
                    soundName: 'entity.player.levelup',
                    x: $player->getPosition()->getX(),
                    y: $player->getPosition()->getY(),
                    z: $player->getPosition()->getZ(),
                    volume: 1.0,
                    pitch: 1.0
                ));
            }
        }
    }

    public function canBeTagged(Player $player): bool
    {
        return ! $player->hasPermission(PermissionIds::BYPASS_COMBAT_TAG) || ! $this->isBannedWorld($player->getWorld());
    }

    public function isTagged(Player $player): bool
    {
        $playerName = strtolower($player->getName());

        if (! isset($this->combatData[$playerName])) {
            return false;
        }

        if ($this->combatData[$playerName]['expireTime'] <= time()) {
            $this->untag($player);
            return false;
        }

        return true;
    }

    public function getRemainingCombatTime(Player $player): ?int
    {
        $playerName = strtolower($player->getName());

        if (! isset($this->combatData[$playerName])) {
            return null;
        }

        $remaining = $this->combatData[$playerName]['expireTime'] - time();
        return $remaining > 0 ? $remaining : null;
    }

    public function getMainOpponent(Player $player): ?string
    {
        $playerName = strtolower($player->getName());

        if (! isset($this->combatData[$playerName]) || empty($this->combatData[$playerName]['opponents'])) {
            return null;
        }

        $opponents = $this->combatData[$playerName]['opponents'];
        arsort($opponents);

        return key($opponents);
    }

    public function getAllOpponents(Player $player): array
    {
        $playerName = strtolower($player->getName());

        if (! isset($this->combatData[$playerName])) {
            return [];
        }

        return $this->combatData[$playerName]['opponents'];
    }

    public function isCommandBanned(string $command): bool
    {
        $command = ltrim(strtolower($command), '/');

        foreach ($this->bannedCommands as $bannedCommand) {
            if (str_starts_with($command, strtolower($bannedCommand))) {
                return true;
            }
        }

        return false;
    }


    public function isBannedWorld(World|string $world): bool
    {
        $worldName = $world instanceof World ? $world->getFolderName() : $world;
        return in_array(strtolower($worldName), array_map('strtolower', $this->bannedWorlds), true);
    }

    public function cleanupInactiveOpponents(Player $player, int $olderThan = 10): void
    {
        $playerName = strtolower($player->getName());

        if (! isset($this->combatData[$playerName])) {
            return;
        }

        $currentTime = time();
        $cutoffTime  = $currentTime - $olderThan;
        $hadOpponentsBefore = ! empty($this->combatData[$playerName]['opponents']);

        foreach ($this->combatData[$playerName]['opponents'] as $opponent => $lastHitTime) {
            if ($lastHitTime < $cutoffTime) {
                unset($this->combatData[$playerName]['opponents'][$opponent]);
            }
        }

        if ($hadOpponentsBefore && empty($this->combatData[$playerName]['opponents'])) {
            if ($this->noticePlayer) {
                MessagesUtils::sendTo($player, MessagesIds::ALL_OPPONENTS_LEFT_COMBAT);
            }
        }
    }

    public function noticePlayer(): bool
    {
        return $this->noticePlayer;
    }

    public function canKillOnDisconnect(): bool
    {
        return $this->killOnDisconnect;
    }

}