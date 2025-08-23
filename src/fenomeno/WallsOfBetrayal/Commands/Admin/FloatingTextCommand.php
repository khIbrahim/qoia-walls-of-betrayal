<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\FloatingText\CreateFloatingTextSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\FloatingText\DeleteFloatingTextSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\FloatingText\EditFloatingTextSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\FloatingText\ListFloatingTextSubCommand;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class FloatingTextCommand extends WCommand
{

    protected function prepare(): void
    {
        $this->registerSubCommand(new CreateFloatingTextSubCommand($this->main));
        $this->registerSubCommand(new DeleteFloatingTextSubCommand($this->main));
        $this->registerSubCommand(new EditFloatingTextSubCommand($this->main));
        $this->registerSubCommand(new ListFloatingTextSubCommand($this->main));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage($this->getUsage());
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::FLOATING_TEXT);
    }
}