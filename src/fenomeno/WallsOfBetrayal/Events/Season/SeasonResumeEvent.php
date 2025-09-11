<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use pocketmine\event\Event;


class SeasonResumeEvent extends Event
{

    public function __construct(
        private readonly SeasonDTO $season,
        private readonly int       $pauseDuration
    ) {}

    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }

    public function getPauseDuration(): int
    {
        return $this->pauseDuration;
    }

    public function getFormattedPauseDuration(): string
    {
        return DurationParser::getReadableDuration($this->pauseDuration);
    }
}
