<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberHireSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberFireSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberListSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberInfoSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberRoleSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberShiftSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberSalesSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Store\StoreMemberStatsSubCommand;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberCommand extends WCommand {

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void {
        $this->addConstraint(new InGameRequiredConstraint($this));

        // Register subcommands
        $this->registerSubCommand(new StoreMemberHireSubCommand($this->main, "hire", "Hire a new store member"));
        $this->registerSubCommand(new StoreMemberFireSubCommand($this->main, "fire", "Fire a store member"));
        $this->registerSubCommand(new StoreMemberListSubCommand($this->main, "list", "List store members"));
        $this->registerSubCommand(new StoreMemberInfoSubCommand($this->main, "info", "Get member information"));
        $this->registerSubCommand(new StoreMemberRoleSubCommand($this->main, "role", "Manage member roles"));
        $this->registerSubCommand(new StoreMemberShiftSubCommand($this->main, "shift", "Manage member shifts"));
        $this->registerSubCommand(new StoreMemberSalesSubCommand($this->main, "sales", "Process sales transactions"));
        $this->registerSubCommand(new StoreMemberStatsSubCommand($this->main, "stats", "View member statistics"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);

        $this->sendUsage($sender);
    }

    private function sendUsage(Player $player): void {
        $player->sendMessage(TextFormat::GOLD . "=== Store Member Commands ===");
        $player->sendMessage(TextFormat::YELLOW . "/storemember hire <player> <store> <role> [salary] [commission] - Hire a new member");
        $player->sendMessage(TextFormat::YELLOW . "/storemember fire <player> <store> - Fire a member");
        $player->sendMessage(TextFormat::YELLOW . "/storemember list <store> [page] - List store members");
        $player->sendMessage(TextFormat::YELLOW . "/storemember info <player> <store> - Get member info");
        $player->sendMessage(TextFormat::YELLOW . "/storemember role <player> <store> <role> - Change member role");
        $player->sendMessage(TextFormat::YELLOW . "/storemember shift <start|end|status> - Manage shifts");
        $player->sendMessage(TextFormat::YELLOW . "/storemember sales <amount> <method> [items...] - Process sale");
        $player->sendMessage(TextFormat::YELLOW . "/storemember stats <player> <store> [days] - View statistics");
    }

    public function getCommandDTO(): CommandDTO {
        return CommandsConfig::getCommandById(CommandsIds::STORE_MEMBER);
    }
}