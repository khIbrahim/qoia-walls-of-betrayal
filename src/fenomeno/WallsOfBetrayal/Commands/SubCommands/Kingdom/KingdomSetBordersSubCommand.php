<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class KingdomSetBordersSubCommand extends WSubCommand
{

    protected function prepare(): void
    {
        // TODO: Implement prepare() method.
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        // TODO: Implement onRun() method.
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SET_BORDERS);
    }
}