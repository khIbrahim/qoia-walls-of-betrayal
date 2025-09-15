<?php

namespace fenomeno\WallsOfBetrayal\Commands;

use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberAddSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberClockInSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberClockOutSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberDemoteSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberInfoSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberListSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberPermissionsSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberPromoteSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberRemoveSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberSessionsSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberStatsSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember\StoreMemberStatusSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class StoreMemberCommand extends WCommand
{
    protected function prepare(): void
    {
        // Management subcommands (require higher permissions)
        $this->registerSubCommand(new StoreMemberAddSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberRemoveSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberPromoteSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberDemoteSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberStatusSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberPermissionsSubCommand($this->main));
        
        // Information and listing subcommands
        $this->registerSubCommand(new StoreMemberListSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberInfoSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberStatsSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberSessionsSubCommand($this->main));
        
        // Work management subcommands
        $this->registerSubCommand(new StoreMemberClockInSubCommand($this->main));
        $this->registerSubCommand(new StoreMemberClockOutSubCommand($this->main));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (empty($args)) {
            $this->sendHelp($sender);
            return;
        }

        parent::onRun($sender, $aliasUsed, $args);
    }

    private function sendHelp(CommandSender $sender): void
    {
        $sender->sendMessage("§6§l--- Store Member Commands ---");
        $sender->sendMessage("§e/storemember add <player> <role> §7- Add a new store member");
        $sender->sendMessage("§e/storemember remove <player> §7- Remove a store member");
        $sender->sendMessage("§e/storemember list [role] [status] §7- List store members");
        $sender->sendMessage("§e/storemember info [player] §7- View member information");
        $sender->sendMessage("§e/storemember promote <player> <role> §7- Promote a member");
        $sender->sendMessage("§e/storemember demote <player> <role> §7- Demote a member");
        $sender->sendMessage("§e/storemember status <player> <status> §7- Change member status");
        $sender->sendMessage("§e/storemember permissions <player> <add|remove> <permission> §7- Manage permissions");
        $sender->sendMessage("§e/storemember clockin §7- Start work session");
        $sender->sendMessage("§e/storemember clockout §7- End work session");
        $sender->sendMessage("§e/storemember stats [player] §7- View statistics");
        $sender->sendMessage("§e/storemember sessions [player] §7- View session history");
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STORE_MEMBER);
    }
}