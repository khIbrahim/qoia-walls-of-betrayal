<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\Commands\WSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberSalesSubCommand extends WSubCommand {

    protected function prepare(): void {
        // TODO: Implement sales processing
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);
        $sender->sendMessage(TextFormat::YELLOW . "Sales processing feature coming soon!");
    }
}