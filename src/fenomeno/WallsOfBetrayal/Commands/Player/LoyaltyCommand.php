<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Loyalty\LoyaltyCausesSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Loyalty\LoyaltyScoreSubCommand;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class LoyaltyCommand extends WCommand
{

    protected function prepare(): void
    {
        $this->registerSubCommand(new LoyaltyCausesSubCommand($this->main));
        $this->registerSubCommand(new LoyaltyScoreSubCommand($this->main));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage($this->getUsage());
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::LOYALTY);
    }
}