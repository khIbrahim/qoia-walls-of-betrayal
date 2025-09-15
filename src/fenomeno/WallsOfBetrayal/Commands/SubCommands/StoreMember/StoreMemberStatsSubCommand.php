<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;

class StoreMemberStatsSubCommand extends WSubCommand
{
    protected function prepare(): void
    {
        // TODO: Implement stats subcommand
    }

    public function onRun($sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("§cStats command not yet implemented");
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STORE_MEMBER_STATS);
    }
}
