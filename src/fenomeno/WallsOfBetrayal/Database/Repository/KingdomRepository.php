<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomBase;
use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomSanction;
use fenomeno\WallsOfBetrayal\Class\KingdomData;
use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomDataFilterArgument;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KingdomRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\InsertKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\ContributeKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\KingdomRallyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\LoadKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Sanction\CreateKingdomSanctionPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\UpdateKingdomSpawnPayload;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\PositionParser;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use Throwable;

class KingdomRepository implements KingdomRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    /**
     * ici, on hardcode les kingdoms gorvok et thragmar, vu que c'est la V1.
     * On pourrait opter vers une approche plus dynamique dans le futur, ou les kingdoms seraient stockés dans une base de données
     * et créés par un système de configuration ou d'API ou joueurs et modifiés par les admins/joueur en jeu.
     *
     * @param DatabaseManager $database
     * @return void
     */
    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_KINGDOMS, [], function () use ($database) {
            $this->main->getLogger()->info("§aTable `kingdoms` has been successfully init");
        });

        $database->executeGeneric(Statements::INIT_KINGDOM_SANCTIONS, [], function () use ($database) {
            $this->main->getLogger()->info("§aTable `kingdom_sanctions` has been successfully init");
        });
    }

    public function load(LoadKingdomPayload $payload): void
    {
        $this->main->getDatabaseManager()->executeSelect(Statements::LOAD_KINGDOM, $payload->jsonSerialize(), function (array $data) use ($payload) {
            $kingdom = $this->main->getKingdomManager()->getKingdomById($payload->kingdomId);

            if (empty($data)) {
                $this->insert(new InsertKingdomPayload($payload->kingdomId));
                $kingdom->setKingdomData(new KingdomData());
                return;
            }

            try {
                $data = $data[0];
                $kingdomData = new KingdomData(
                    xp: (int) $data['xp'],
                    balance: (int) $data['balance'],
                    kills: (int) $data['kills'],
                    deaths: (int) $data['deaths'],
                    spawn: isset($data['spawn']) ? PositionParser::load(json_decode($data['spawn'], true)) : null,
                    rallyPoint: isset($data['rally_point']) ? PositionParser::load(json_decode($data['rally_point'], true)) : null,
                );

                $kingdom->setKingdomData($kingdomData);

                $this->main->getLogger()->info("§aKingdom data for ID: $payload->kingdomId has been successfully loaded");
            } catch (Throwable $e) {Utils::onFailure($e, null, "Failed to load kingdom data for ID: " . $payload->kingdomId);}

            if (isset($data['borders'])){
                try {
                    $kingdom->setBase(KingdomBase::fromArray((array) json_decode((string) $data['borders'], true)));
                } catch (Throwable $e) {Utils::onFailure($e, null, "Failed to load kingdom borders for kingdom " . $payload->kingdomId);}
            }
        }, fn(Throwable $e) => $this->main->getLogger()->error("§cAn error occurred while loading kingdom data for ID: $payload->kingdomId"));
    }

    public function insert(InsertKingdomPayload $payload): void
    {
        $this->main->getDatabaseManager()->executeInsert(Statements::INSERT_KINGDOM, $payload->jsonSerialize(), function () use ($payload) {
            $this->main->getLogger()->info("§aKingdom $payload->kingdomId has been successfully inserted");
        }, fn(Throwable $e) => $this->main->getLogger()->error("§cAn error occurred while inserting kingdom data. Error: " . $e->getMessage()));
    }

    public function addDeath(IdPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::INCREMENT_KINGDOM_DEATHS, $payload->jsonSerialize());
    }

    public function addKill(IdPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::INCREMENT_KINGDOM_KILLS, $payload->jsonSerialize());
    }

    public function updateSpawn(UpdateKingdomSpawnPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_KINGDOM_SPAWN, $payload->jsonSerialize());
    }

    public function getTotalMembers(IdPayload $payload): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(
            Statements::GET_KINGDOMS_PLAYERS_COUNT,
            $payload->jsonSerialize()
        );

        if (empty($rows)) {
            return 0;
        }

        return $rows[0]['total'] ?? 0;
    }

    public function contribute(ContributeKingdomPayload $payload): Generator
    {
        $queryName = match ($payload->type) {
            KingdomDataFilterArgument::XP => Statements::ADD_KINGDOM_XP,
            KingdomDataFilterArgument::BALANCE => Statements::ADD_KINGDOM_BALANCE,
        };

        return yield from $this->main->getDatabaseManager()->asyncChange($queryName, $payload->jsonSerialize());
    }

    public function setRally(KingdomRallyPayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_KINGDOM_RALLY_POINT, $payload->jsonSerialize());
    }

    /**
     * @param CreateKingdomSanctionPayload $payload
     * @return Generator<int>
     */
    public function createSanction(CreateKingdomSanctionPayload $payload): Generator
    {
        [$insertId,] = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::CREATE_KINGDOM_SANCTION, $payload->jsonSerialize());

        return $insertId;
    }

    public function deactivateSanction(string $kingdomId, string $targetUuid): Generator
    {
        $result = yield from $this->main->getDatabaseManager()->asyncChange(
            Statements::DEACTIVATE_KINGDOM_SANCTION,
            ['kingdom_id' => $kingdomId, 'uuid' => $targetUuid]
        );

        return $result > 0;
    }

    public function isSanctioned(string $kingdomId, string $targetUuid, int $currentTime): Generator
    {
        $result = yield from $this->main->getDatabaseManager()->asyncSelect(
            Statements::IS_PLAYER_SANCTIONED,
            ['kingdom_id' => $kingdomId, 'uuid' => $targetUuid, 'time' => $currentTime]
        );

        return !empty($result);
    }

    public function loadActiveSanctions(callable $onSuccess, callable $onFailure): void
    {
        $this->main->getDatabaseManager()->executeSelect(Statements::LOAD_ACTIVE_SANCTIONS, ['time' => time()], function (array $rows) use ($onSuccess): void {
            $sanctions = [];
            foreach ($rows as $row) {
                try {
                    $sanctions[] = KingdomSanction::fromArray($row);
                } catch (Throwable $e) {
                    Utils::onFailure($e, null, "Failed to load kingdom sanction from database row: " . json_encode($row));
                }
            }
            $onSuccess($sanctions);
        }, $onFailure);
    }

    public function getPlayerSanctionHistory(string $targetUuid): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_PLAYER_SANCTION_HISTORY, ['uuid' => $targetUuid]);

        $sanctions = [];
        foreach ($rows as $row) {
            try {
                $sanctions[] = KingdomSanction::fromArray($row);
            } catch (Throwable $e) {
                Utils::onFailure($e, null, "Failed to load kingdom sanction from database row & history sanction: " . json_encode($row));
            }
        }

        return $sanctions;
    }

    public function updateKingdomBorders(string $id, array $borders): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_KINGDOM_BORDERS, [
            'id'      => $id,
            'borders' => json_encode($borders),
        ]);
    }

    public static function getQueriesFile(): string
    {
        return 'queries/mysql/kingdoms.sql';
    }
}