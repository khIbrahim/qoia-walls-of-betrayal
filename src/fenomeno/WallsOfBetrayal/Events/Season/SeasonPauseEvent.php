<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use pocketmine\event\Event;

/**
 * Événement déclenché lorsqu'une saison est mise en pause
 */
class SeasonPauseEvent extends Event
{
    /**
     * @param SeasonDTO $season La saison mise en pause
     * @param string $reason Raison de la mise en pause
     */
    public function __construct(
        protected SeasonDTO $season,
        protected string $reason
    ) {}

    /**
     * Récupère la saison mise en pause
     */
    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }

    /**
     * Récupère la raison de la mise en pause
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
