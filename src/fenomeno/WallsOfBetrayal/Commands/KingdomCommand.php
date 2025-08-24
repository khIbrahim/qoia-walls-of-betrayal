<?php

namespace fenomeno\WallsOfBetrayal\Commands;

use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomAbilitiesSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomAddXpSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomAlarmSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomBanSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomBountySubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomContributeSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomInfoSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomKickSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomManageSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomMapSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomPeaceSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomRallySubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomSetBordersSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomShieldSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomSpawnSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomTopSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomTruceSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomUpgradeSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomVoteSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom\KingdomWarSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use pocketmine\command\CommandSender;

class KingdomCommand extends WCommand
{

    protected function prepare(): void
    {
        $this->registerSubCommand(new KingdomSpawnSubCommand($this->main));
        $this->registerSubCommand(new KingdomInfoSubCommand($this->main));
        $this->registerSubCommand(new KingdomTopSubCommand($this->main));
        $this->registerSubCommand(new KingdomMapSubCommand($this->main));
        $this->registerSubCommand(new KingdomContributeSubCommand($this->main));
        $this->registerSubCommand(new KingdomAbilitiesSubCommand($this->main));
        $this->registerSubCommand(new KingdomAddXpSubCommand($this->main));
        $this->registerSubCommand(new KingdomSetBordersSubCommand($this->main));
        $this->registerSubCommand(new KingdomManageSubCommand($this->main));
        $this->registerSubCommand(new KingdomKickSubCommand($this->main));
        $this->registerSubCommand(new KingdomBanSubCommand($this->main));
        $this->registerSubCommand(new KingdomVoteSubCommand($this->main));
        $this->registerSubCommand(new KingdomTruceSubCommand($this->main));
        $this->registerSubCommand(new KingdomWarSubCommand($this->main));
        $this->registerSubCommand(new KingdomPeaceSubCommand($this->main));
        $this->registerSubCommand(new KingdomUpgradeSubCommand($this->main));
        $this->registerSubCommand(new KingdomShieldSubCommand($this->main));
        $this->registerSubCommand(new KingdomAlarmSubCommand($this->main));
        $this->registerSubCommand(new KingdomRallySubCommand($this->main));
        $this->registerSubCommand(new KingdomBountySubCommand($this->main));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {

    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM);
    }
}