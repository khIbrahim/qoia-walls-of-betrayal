<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Season;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class ListSeasonsSubCommand extends WSubCommand
{

    protected function prepare(): void
    {

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $seasons = $this->main->getSeasonManager()->getAllSeasons();
        if (count($seasons) === 0) {
            MessagesUtils::sendTo($sender, MessagesIds::SEASON_NO_SEASONS);
            return;
        }

        $sender->sendMessage(MessagesUtils::getMessage(MessagesIds::SEASON_LIST_HEADER));
        /** @var SeasonDTO $season */
        foreach ($seasons as $season){
            $getTag = function (SeasonDTO $s){
                if ($s->isActive()) {
                    return MessagesUtils::getMessage(MessagesIds::SEASON_LIST_ACTIVE_TAG);
                }
                if ($s->isPaused()) {
                    return MessagesUtils::getMessage(MessagesIds::SEASON_LIST_PAUSED_TAG);
                }
                if (! $s->isActive() && $s->actualEndTime !== null) {
                    return MessagesUtils::getMessage(MessagesIds::SEASON_LIST_ENDED_TAG);
                }
                return "";
            };

            MessagesUtils::sendTo($sender, MessagesIds::SEASON_LIST_ENTRY, [
                ExtraTags::TAG      => $getTag($season),
                ExtraTags::NUMBER   => $season->seasonNumber,
                ExtraTags::NAME     => $season->name,
                ExtraTags::THEME    => $season->theme,
                ExtraTags::DURATION => $season->getDurationDays(),
            ]);
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SEASON_LIST);
    }
}