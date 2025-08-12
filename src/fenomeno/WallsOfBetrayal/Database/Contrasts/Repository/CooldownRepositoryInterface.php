<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\GetActiveCooldownsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\RemoveCooldownPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\UpsertCooldownPayload;

interface CooldownRepositoryInterface
{

    public function getAll(GetActiveCooldownsPayload $payload): \Generator;

    public function upsert(UpsertCooldownPayload $payload): \Generator;

    public function remove(RemoveCooldownPayload $payload): \Generator;

}