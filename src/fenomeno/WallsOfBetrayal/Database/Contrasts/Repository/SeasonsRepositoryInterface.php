<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\Player\UpdateSeasonPlayerStats;
use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\SaveSeasonPayload;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use Generator;


interface SeasonsRepositoryInterface extends RepositoryInterface
{

    /**
     * Load the current active season
     * @return Generator<array{0: SeasonDTO}|null>
     */
    public function loadCurrentSeason(): Generator;

    /**
     * Load a season by its ID
     * @return Generator<array{0: SeasonDTO}|null>
     */
    public function loadSeasonById(IdPayload $payload): Generator;

    /**
     * Load the history of all seasons
     * @return Generator<array<int, SeasonDTO>>
     */
    public function loadSeasonHistory(): Generator;

    /**
     * Save a new season
     * @return Generator<array{0: SeasonDTO}|null>
     */
    public function saveSeason(SaveSeasonPayload $payload): Generator;

    /**
     * Update an existing season
     * @return Generator<array{0: SeasonDTO}|null>
     */
    public function updateSeason(SaveSeasonPayload $payload): Generator;

    public function updatePlayerStats(UpdateSeasonPlayerStats $payload): Generator;

//
//    /**
//     * Charge les statistiques d'un joueur pour une saison spécifique
//     */
//    public function loadPlayerSeasonStats(string $playerId, int $seasonId): Generator;
//
//    /**
//     * Sauvegarde les statistiques d'un joueur pour une saison
//     */
//    public function savePlayerSeasonStats(string $playerId, int $seasonId, array $stats): Generator;
//
//    /**
//     * Récupère le classement des joueurs pour une saison
//     */
//    public function getSeasonLeaderboard(int $seasonId, int $limit = 10): Generator;
//
//    /**
//     * Récupère tous les participants d'une saison
//     */
//    public function getSeasonParticipants(int $seasonId): Generator;
//
//    /**
//     * Sauvegarde les récompenses d'un joueur pour une saison
//     */
//    public function savePlayerSeasonRewards(string $playerId, int $seasonId, array $rewards): Generator;
//
//    /**
//     * Vérifie si un joueur a déjà réclamé ses récompenses pour une saison
//     */
//    public function hasPlayerClaimedRewards(string $playerId, int $seasonId): Generator;
//
//    /**
//     * Marque les récompenses d'un joueur comme réclamées
//     */
//    public function markPlayerRewardsAsClaimed(string $playerId, int $seasonId): Generator;
}
