<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\Commands\WSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberRoleSubCommand extends WSubCommand {

    protected function prepare(): void {
        // TODO: Implement role management
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);
        $sender->sendMessage(TextFormat::YELLOW . "Role management feature coming soon!");
    }
}