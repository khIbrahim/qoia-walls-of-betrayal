<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use pocketmine\event\Event;

class SeasonStartEvent extends Event
{

    public function __construct(
        private readonly SeasonDTO $season
    ) {}

    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }
}
