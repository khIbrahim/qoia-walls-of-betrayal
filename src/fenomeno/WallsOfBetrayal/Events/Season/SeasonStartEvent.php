<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use pocketmine\event\Event;

/**
 * Événement déclenché lorsqu'une saison démarre
 */
class SeasonStartEvent extends Event
{
    /**
     * @param SeasonDTO $season La saison qui démarre
     */
    public function __construct(
        protected SeasonDTO $season
    ) {}

    /**
     * Récupère la saison qui démarre
     */
    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }
}
