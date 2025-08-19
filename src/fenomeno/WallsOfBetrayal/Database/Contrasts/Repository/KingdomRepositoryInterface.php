<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\InsertKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\LoadKingdomPayload;

interface KingdomRepositoryInterface extends RepositoryInterface
{

    public function load(LoadKingdomPayload $payload): void;

    public function insert(InsertKingdomPayload $payload): void;

    public function addDeath(IdPayload $payload): \Generator;

    public function addKill(IdPayload $payload): \Generator;

}