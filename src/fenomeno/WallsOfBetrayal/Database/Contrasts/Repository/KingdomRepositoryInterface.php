<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\InsertKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\ContributeKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\KingdomRallyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\LoadKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Sanction\CreateKingdomSanctionPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\UpdateKingdomSpawnPayload;
use Generator;

interface KingdomRepositoryInterface extends RepositoryInterface
{

    public function load(LoadKingdomPayload $payload): void;

    public function insert(InsertKingdomPayload $payload): void;

    public function addDeath(IdPayload $payload): Generator;

    public function addKill(IdPayload $payload): Generator;

    public function updateSpawn(UpdateKingdomSpawnPayload $payload): Generator;

    public function getTotalMembers(IdPayload $payload): Generator;

    public function contribute(ContributeKingdomPayload $payload): Generator;

    public function setRally(KingdomRallyPayload $payload): Generator;

    public function createSanction(CreateKingdomSanctionPayload $payload): Generator;

    public function deactivateSanction(string $kingdomId, string $targetUuid): Generator;

    public function updateKingdomBorders(string $id, array $borders): Generator;

}