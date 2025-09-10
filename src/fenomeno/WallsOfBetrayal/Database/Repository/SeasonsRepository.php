<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\SeasonsRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\SaveSeasonPayload;
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

    public static function getQueriesFile(): string
    {
        return 'queries/mysql/seasons.sql';
    }
}
