<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use pocketmine\event\Event;

class SeasonPauseEvent extends Event
{

    public function __construct(
        private readonly SeasonDTO $season,
        private string $reason
    ) {}

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
