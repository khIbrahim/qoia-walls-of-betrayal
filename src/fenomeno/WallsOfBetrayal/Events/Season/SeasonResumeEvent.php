<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use pocketmine\event\Event;

/**
 * Événement déclenché lorsqu'une saison en pause est reprise
 */
class SeasonResumeEvent extends Event
{
    /**
     * @param SeasonDTO $season La saison reprise
     * @param int $pauseDuration Durée de la pause en secondes
     */
    public function __construct(
        protected SeasonDTO $season,
        protected int $pauseDuration
    ) {}

    /**
     * Récupère la saison reprise
     */
    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }

    /**
     * Récupère la durée de la pause
     */
    public function getPauseDuration(): int
    {
        return $this->pauseDuration;
    }

    /**
     * Récupère la durée de la pause formatée
     */
    public function getFormattedPauseDuration(): string
    {
        $days = floor($this->pauseDuration / 86400);
        $hours = floor(($this->pauseDuration % 86400) / 3600);
        $minutes = floor(($this->pauseDuration % 3600) / 60);

        $formatted = '';
        if ($days > 0) {
            $formatted .= $days . ' jour' . ($days > 1 ? 's' : '') . ' ';
        }
        if ($hours > 0 || $days > 0) {
            $formatted .= $hours . ' heure' . ($hours > 1 ? 's' : '') . ' ';
        }
        $formatted .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');

        return $formatted;
    }
}
