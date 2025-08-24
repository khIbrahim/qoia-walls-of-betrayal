<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class KingdomInfoSubCommand extends WSubCommand
{

    protected function prepare(): void
    {
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("TODO");
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_INFO);
    }
}