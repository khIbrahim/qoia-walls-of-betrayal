<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Season\EndSeasonSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Season\InfoSeasonSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Season\ListSeasonsSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Season\PauseSeasonSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Season\ResumeSeasonSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Season\StartSeasonSubCommand;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class SeasonCommand extends WCommand
{

    protected function prepare(): void
    {
        $this->registerSubCommand(new StartSeasonSubCommand($this->main));
        $this->registerSubCommand(new PauseSeasonSubCommand($this->main));
        $this->registerSubCommand(new EndSeasonSubCommand($this->main));
        $this->registerSubCommand(new ResumeSeasonSubCommand($this->main));
        $this->registerSubCommand(new InfoSeasonSubCommand($this->main));
        $this->registerSubCommand(new ListSeasonsSubCommand($this->main));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage($this->getUsage());
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SEASON);
    }
}