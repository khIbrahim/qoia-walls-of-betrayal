<?php

namespace fenomeno\WallsOfBetrayal\Game\Season;

use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\Events\Season\SeasonPauseEvent;
use fenomeno\WallsOfBetrayal\Events\Season\SeasonResumeEvent;
use fenomeno\WallsOfBetrayal\Events\Season\SeasonStartEvent;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;

class SeasonEvents implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();
        if(! $this->main->getSeasonManager()->isSeasonActive()){
            return;
        }

        if(! $sender->hasPermission(PermissionIds::BYPASS_SEASON)){
            return;
        }

        $event->cancel();
        MessagesUtils::sendTo($sender, MessagesIds::CANCELLED_DUE_TO_SEASON_OFF);
    }

    public function onStart(SeasonStartEvent $event): void
    {
        $season = $event->getSeason();
        MessagesUtils::broadcastMessage(MessagesIds::SEASON_STARTED, [
            ExtraTags::NUMBER   => $season->seasonNumber,
            ExtraTags::NAME     => $season->name,
            ExtraTags::THEME    => $season->theme,
            ExtraTags::DURATION => $season->getDurationDays()
        ]);
    }

    public function onPause(SeasonPauseEvent $event): void
    {
        $season = $event->getSeason();
        $reason = $event->getReason();
        MessagesUtils::broadcastMessage(MessagesIds::SEASON_PAUSED, [
            ExtraTags::NUMBER => $season->seasonNumber,
            ExtraTags::NAME   => $season->name,
            ExtraTags::REASON => $reason
        ]);
    }

    public function onResume(SeasonResumeEvent $event): void
    {
        $season = $event->getSeason();

        MessagesUtils::broadcastMessage(MessagesIds::SEASON_RESUMED, [
            ExtraTags::NUMBER   => $season->seasonNumber,
            ExtraTags::NAME     => $season->name,
            ExtraTags::DURATION => $event->getFormattedPauseDuration()
        ]);
    }

}