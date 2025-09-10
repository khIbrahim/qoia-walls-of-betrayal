<?php

namespace fenomeno\WallsOfBetrayal\Events\Season;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use pocketmine\event\Event;

/**
 * Événement déclenché lorsqu'une saison avance manuellement
 */
class SeasonAdvanceEvent extends Event
{
    /**
     * @param SeasonDTO $season La saison concernée
     * @param int $daysAdvanced Nombre de jours avancés
     */
    public function __construct(
        protected SeasonDTO $season,
        protected int $daysAdvanced
    ) {}

    /**
     * Récupère la saison concernée
     */
    public function getSeason(): SeasonDTO
    {
        return $this->season;
    }

    /**
     * Récupère le nombre de jours avancés
     */
    public function getDaysAdvanced(): int
    {
        return $this->daysAdvanced;
    }
}
