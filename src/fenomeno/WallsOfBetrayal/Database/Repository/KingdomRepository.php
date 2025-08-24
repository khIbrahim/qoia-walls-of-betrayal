<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Class\KingdomData;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KingdomRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\InsertKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\LoadKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\UpdateKingdomSpawnPayload;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\PositionHelper;
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
        $database->executeGeneric(Statements::INIT_KINGDOMS, [], function () {
            $this->main->getLogger()->info("§aTable `kingdoms` has been successfully init");
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
                    spawn: isset($data['spawn']) ? PositionHelper::load(json_decode($data['spawn'])) : null
                );

                $kingdom->setKingdomData($kingdomData);

                $this->main->getLogger()->info("§aKingdom data for ID: $payload->kingdomId has been successfully loaded");
            } catch (Throwable $e) {
                $this->main->getLogger()->error("§cFailed to load kingdom data for ID: $payload->kingdomId. Error: " . $e->getMessage());
            }
        }, fn(Throwable $e) => $this->main->getLogger()->error("§cAn error occurred while loading kingdom data for ID: $payload->kingdomId. Error: " . $e->getMessage()));
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

}