<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KingdomSpawnSubCommand extends WSubCommand
{
    protected function prepare(): void
    {
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_PLAYER);
            return;
        }

        $session = Session::get($sender);
        if (!$session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [ExtraTags::PLAYER => $sender->getName()]);
            return;
        }

        $kingdom = $session->getKingdom();
        if ($kingdom === null) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_SPAWN_NO_KINGDOM);
            return;
        }

        // Vérifier le cooldown de téléportation
        if (!$this->canTeleport($sender)) {
            $cooldown = $this->getTeleportCooldown($sender);
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_RALLY_COOLDOWN, [
                ExtraTags::TIME => $cooldown
            ]);
            return;
        }

        $spawn = $kingdom->getSpawn();
        if ($spawn === null) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_SPAWN_NO_SPAWN);
            return;
        }

        // Téléporter le joueur
        $sender->teleport($spawn);
        $this->setTeleportCooldown($sender);

        MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_SPAWN_SUCCESS, [
            ExtraTags::KINGDOM => $kingdom->displayName
        ]);
    }

    private function canTeleport(Player $player): bool
    {
        // TODO: Vérifier le cooldown de téléportation
        return true;
    }

    private function getTeleportCooldown(Player $player): string
    {
        // TODO: Calculer le temps restant du cooldown
        return "30s";
    }

    private function setTeleportCooldown(Player $player): void
    {
        // TODO: Définir le cooldown de téléportation
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SPAWN);
    }
}