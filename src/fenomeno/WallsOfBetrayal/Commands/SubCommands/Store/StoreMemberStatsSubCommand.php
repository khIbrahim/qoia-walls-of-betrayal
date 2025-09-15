<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\Commands\WSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberStatsSubCommand extends WSubCommand {

    protected function prepare(): void {
        // TODO: Implement statistics viewing
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);
        $sender->sendMessage(TextFormat::YELLOW . "Statistics feature coming soon!");
    }
}