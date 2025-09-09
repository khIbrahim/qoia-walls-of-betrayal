<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\Constants\CooldownTypes;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;
use Throwable;

class KingdomSpawnSubCommand extends WSubCommand
{

    private const KINGDOM_ARGUMENT = 'kingdom';
    private const PLAYER_ARGUMENT  = 'player';
    private const DEFAULT_COOLDOWN = 250;


    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new KingdomArgument(self::KINGDOM_ARGUMENT, true));
        $this->registerArgument(1, new TargetPlayerArgument(self::PLAYER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = Session::get($sender);
        if (! $session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [ExtraTags::PLAYER => $sender->getName()]);
            return;
        }

        if (isset($args[self::KINGDOM_ARGUMENT])){
            $kingdom = $args[self::KINGDOM_ARGUMENT];
            if ($kingdom === null) {
                MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_KINGDOM, [ExtraTags::KINGDOM => (string) $args[self::KINGDOM_ARGUMENT]]);
                return;
            }

            if($session->getKingdom() !== null && $session->getKingdom()->getId() !== $kingdom->getId() && ! $sender->hasPermission(PermissionIds::KINGDOM_SPAWN_OTHER)){
                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_SPAWN_NOT_YOUR_KINGDOM, [ExtraTags::KINGDOM => $kingdom->displayName]);
                return;
            }
        } else {
            $kingdom = $session->getKingdom();
            if ($kingdom === null) {
                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_SPAWN_NO_KINGDOM);
                return;
            }
        }

        $playerName = isset($args[self::PLAYER_ARGUMENT]) ? (string) $args[self::PLAYER_ARGUMENT] : $sender->getName();
        $player     = $sender->getServer()->getPlayerExact($playerName);
        if (! $player instanceof Player) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        $itSelf = strtolower($sender->getName()) === strtolower($player->getName());
        if (! $itSelf && ! $sender->hasPermission(PermissionIds::KINGDOM_SPAWN_OTHER)) {
            MessagesUtils::sendTo($sender, MessagesIds::NO_PERMISSION);
            return;
        }

        if (! $this->canTeleport($player, $itSelf)) {
            $cooldown = $this->main->getCooldownManager()->getCooldownRemaining(CooldownTypes::KINGDOM_SPAWN, $sender->getName(), true);
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_SPAWN_COOLDOWN, [ExtraTags::TIME => $cooldown]);
            $sender->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
                soundName: 'mob.villager.no',
                x: (float) $sender->getLocation()->x,
                y: (float) $sender->getLocation()->y,
                z: (float) $sender->getLocation()->z,
                volume: 1.0,
                pitch: 1.0
            ));
            return;
        }

        $spawn = $kingdom->getSpawn();
        if ($spawn === null) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_SPAWN_NO_SPAWN);
            return;
        }

        try {
            $player->teleport($spawn);
            $player->broadcastSound(new EndermanTeleportSound());
            $player->getWorld()->addParticle($spawn, new EndermanTeleportParticle());
            if($itSelf && ! $player->hasPermission(PermissionIds::BYPASS_SPAWN_COOLDOWN)){
                $this->setTeleportCooldown($player);
            }

            MessagesUtils::sendTo($sender, $itSelf ? MessagesIds::KINGDOMS_SPAWN_SUCCESS : MessagesIds::KINGDOMS_SPAWN_SUCCESS_OTHER, [
                ExtraTags::KINGDOM => $kingdom->displayName,
                ExtraTags::PLAYER  => $playerName
            ]);
        } catch (Throwable $e){Utils::onFailure($e, $sender, "Failed to teleport {$sender->getName()} to kingdom $kingdom->id spawn");}
    }

    private function canTeleport(Player $player, bool $itSelf = false): bool
    {
        if(! $itSelf){
            return true;
        }

        if ($player->hasPermission(PermissionIds::BYPASS_SPAWN_COOLDOWN)) {
            return true;
        }

        if($this->main->getServerManager()->getLobbyManager()->isInLobby($player)){
            return true;
        }

        return ! $this->main->getCooldownManager()->isOnCooldown(CooldownTypes::KINGDOM_SPAWN, $player->getName());
    }

    private function setTeleportCooldown(Player $player): void
    {
        $this->main->getCooldownManager()->setCooldown(CooldownTypes::KINGDOM_SPAWN, $player->getName(), $this->getCooldown(self::DEFAULT_COOLDOWN));
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SPAWN);
    }
}