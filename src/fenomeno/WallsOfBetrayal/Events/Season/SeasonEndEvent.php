<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use pocketmine\event\Event;

/**
 * Événement déclenché lorsqu'une saison se termine
 */
class SeasonEndEvent extends Event
{
    /**
     * @param SeasonDTO $season La saison qui se termine
     * @param string $reason Raison de la fin de la saison
     */
    public function __construct(
        protected SeasonDTO $season,
        protected string $reason
    ) {}

    /**
     * Récupère la saison qui se termine
     */
    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }

    /**
     * Récupère la raison de la fin de la saison
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
