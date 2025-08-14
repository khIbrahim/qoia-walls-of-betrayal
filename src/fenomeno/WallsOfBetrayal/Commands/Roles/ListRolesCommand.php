<?php

namespace fenomeno\WallsOfBetrayal\Commands\Roles;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class ListRolesCommand extends WCommand
{

    protected function prepare(): void
    {

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $roles = $this->main->getRolesManager()->getRoles();
        if (empty($roles)) {
            $sender->sendMessage("No roles available.");
            return;
        }

        $roleList = implode(", ", array_keys($roles));
        $sender->sendMessage("Available roles: " . $roleList);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::LIST_ROLES);
    }
}