<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Season;

use fenomeno\WallsOfBetrayal\Commands\Arguments\SeasonArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class InfoSeasonSubCommand extends WSubCommand
{

    private const SEASON_ARGUMENT = 'season';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new SeasonArgument(self::SEASON_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $season = $args[self::SEASON_ARGUMENT] ?? $this->main->getSeasonManager()->getCurrentSeason();
        if(! $season){
            MessagesUtils::sendTo($sender, MessagesIds::NO_ACTIVE_SEASON);
            return;
        }

        $sender->sendMessage("TODO");
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SEASON_INFO);
    }
}