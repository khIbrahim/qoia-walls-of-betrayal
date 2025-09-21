<?php

namespace fenomeno\WallsOfBetrayal\Game\Loyalty\AFK;

use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyCause;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\LoyaltyManager;
use fenomeno\WallsOfBetrayal\Task\AfkCheckTask;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\sound\PopSound;

class PlayerActivityTracker
{

    private const DEFAULT_AFK_CHECK_INTERVAL = 12000;
    private const DEFAULT_PLAYTIME_CHECK_INTERVAL = 36000;

    private array $lastActivity = [];
    private array $afkPlayers   = [];

    private readonly int $checkPlaytimeInterval; // 30 minutes in ticks
    private readonly int $checkAFKInterval; // 10 minutes in ticks

    public function __construct(
        private readonly Main $main,
        private readonly LoyaltyManager $loyaltyManager,
        private readonly int $afkTimeout = WobConfig::DEFAULT_AFK_CHECK_INTERVAL,
    ){
        $this->checkAFKInterval      = $this->loyaltyManager->getConfig()->getNested('parameters.afk_interval', self::DEFAULT_AFK_CHECK_INTERVAL);
        $this->checkPlaytimeInterval = $this->loyaltyManager->getConfig()->getNested('parameters.playtime_interval', self::DEFAULT_PLAYTIME_CHECK_INTERVAL);

        $this->main->getScheduler()->scheduleRepeatingTask(new AfkCheckTask($this), 1);
    }

    public function touch(Player $player): void
    {
        $this->lastActivity[$player->getUniqueId()->toString()] = time();

        if (isset($this->afkPlayers[$player->getUniqueId()->toString()])) {
            unset($this->afkPlayers[$player->getUniqueId()->toString()]);
            MessagesUtils::sendTo($player, MessagesIds::NO_LONGER_AFK);
        }
    }

    public function checkAFKPlayers(): void
    {
        $currentTime = time();

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $lastActivity      = $this->lastActivity[$player->getUniqueId()->toString()] ?? $currentTime;
            $timeSinceActivity = $currentTime - $lastActivity;

            if ($timeSinceActivity >= $this->afkTimeout) {
                if(! isset($this->afkPlayers[$player->getUniqueId()->toString()])){
                    $this->afkPlayers[$player->getUniqueId()->toString()] = $currentTime;
                    MessagesUtils::sendTo($player, MessagesIds::YOU_ARE_AFK);
                    $player->broadcastSound(new PopSound());
                }

                $afkTime      = $currentTime - $this->afkPlayers[$player->getUniqueId()->toString()];
                $afkPenalties = intval($afkTime / $this->afkTimeout);
                $afkPenalties = min($afkPenalties, 1);
                if($afkPenalties > 0){
                    $this->loyaltyManager->addLoyalty($player, LoyaltyCause::AFK);
                    $this->afkPlayers[$player->getUniqueId()->toString()] = $currentTime;
                }
            }
        }
    }

    public function checkActivePlayTime(): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if(! isset($this->afkPlayers[$player->getUniqueId()->toString()])){
                $this->loyaltyManager->addLoyalty($player, LoyaltyCause::ACTIVE_PLAYTIME);
            }
        }
    }

    public function cleanup(Player $player): void
    {
        unset($this->lastActivity[$player->getUniqueId()->toString()]);
        unset($this->afkPlayers[$player->getUniqueId()->toString()]);
    }

    public function getCheckAFKInterval(): int
    {
        return $this->checkAFKInterval;
    }

    public function getCheckPlaytimeInterval(): int
    {
        return $this->checkPlaytimeInterval;
    }

}