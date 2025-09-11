<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Class\Season\SeasonKingdom;
use fenomeno\WallsOfBetrayal\Class\Season\SeasonPlayer;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\SeasonsRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\Kingdom\UpdateSeasonKingdomStats;
use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\Player\UpdateSeasonPlayerStats;
use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\SaveSeasonPayload;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\Main;
use Generator;

class SeasonsRepository implements SeasonsRepositoryInterface
{

    public function __construct(private readonly Main $main) {}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_SEASONS, [], function (){
            $this->main->getLogger()->info("§aTable `seasons` has been successfully init");
        });

        $database->executeGeneric(Statements::INIT_SEASON_PLAYERS, [], function (){
            $this->main->getLogger()->info("§aTable `season_players` has been successfully init");
        });

        $database->executeGeneric(Statements::INIT_SEASON_KINGDOMS, [], function (){
            $this->main->getLogger()->info("§aTable `season_kingdoms` has been successfully init");
        });
    }


    public function loadCurrentSeason(): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_CURRENT_SEASON);
        if (empty($rows)){
            return null;
        }

        $seasonData = $rows[0];
        return SeasonDTO::fromArray($seasonData);
    }

    public function loadSeasonById(IdPayload $payload): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_SEASON_BY_ID, $payload->jsonSerialize());
        if( empty($rows)){
            return null;
        }

        $seasonData = $rows[0];
        return SeasonDTO::fromArray($seasonData);
    }

    public function loadSeasonHistory(): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_SEASONS_HISTORY);
        if (empty($rows)){
            return [];
        }

        $seasons = [];
        foreach ($rows as $seasonData) {
            $season = SeasonDTO::fromArray($seasonData);
            $seasons[$season->id] = $season;
        }

        return $seasons;
    }

    public function saveSeason(SaveSeasonPayload $payload): Generator
    {
        $season = $payload->season;

        if ($season->id > 0) {
            return yield from $this->updateSeason($payload);
        }

        /** @var int $insertId */
        [$insertId,] = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::INSERT_SEASON, $payload->jsonSerialize());

        if ($insertId > 0) {
            return yield from $this->loadSeasonById(new IdPayload($insertId));
        } else {
            return null;
        }
    }

    public function updateSeason(SaveSeasonPayload $payload): Generator
    {
        $season = $payload->season;

        /** @var int $affected */
        $affected = yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_SEASON, $payload->jsonSerialize());
        if ($affected <= 0){
            return null;
        }

        return yield from $this->loadSeasonById(new IdPayload($season->id));
    }

    public function loadPlayer(string $playerUuid, int $seasonId): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_SEASON_PLAYER, [
            'player_uuid' => $playerUuid,
            'season_id'   => $seasonId
        ]);

        if (empty($rows)){
            return yield from $this->insertPlayer($playerUuid, $seasonId);
        }

        return SeasonPlayer::fromArray($rows[0]);
    }

    public function insertPlayer(string $playerUuid, int $seasonId): Generator
    {
        /** @var int $insertId */
        [$insertId,] = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::INSERT_SEASON_PLAYER, [
            'player_uuid' => $playerUuid,
            'season_id'   => $seasonId
        ]);

        if ($insertId <= 0){
            return null;
        }

        return yield from $this->loadPlayer($playerUuid, $seasonId);
    }

    public function updatePlayerStats(UpdateSeasonPlayerStats $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_SEASON_PLAYER_STATS, $payload->jsonSerialize());
    }

    public function loadKingdom(string $kingdomId, int $seasonId): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_SEASON_KINGDOM, [
            'kingdom_id' => $kingdomId,
            'season_id'  => $seasonId
        ]);

        if (empty($rows)){
            return yield from $this->insertKingdom($kingdomId, $seasonId);
        }

        return SeasonKingdom::fromArray($rows[0]);
    }

    public function insertKingdom(string $kingdomId, int $seasonId): Generator
    {
        /** @var int $insertId */
        [$insertId,] = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::INSERT_SEASON_KINGDOM, [
            'kingdom_id' => $kingdomId,
            'season_id'  => $seasonId
        ]);

        if ($insertId <= 0){
            return null;
        }

        return yield from $this->loadKingdom($kingdomId, $seasonId);
    }

    public function updateKingdomStats(UpdateSeasonKingdomStats $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_SEASON_KINGDOM_STATS, $payload->jsonSerialize());
    }

    public function getKingdomRankings(int $seasonId): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_SEASON_KINGDOM_RANKINGS, [
            'season_id' => $seasonId
        ]);

        $kingdoms = [];
        foreach ($rows as $row) {
            $kingdoms[] = SeasonKingdom::fromArray($row);
        }

        return $kingdoms;
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/seasons.sql'
            ]
        ];
    }
}
