<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Npc\NpcCreatePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Npc\NpcUpdatePayload;
use Generator;

interface NpcRepositoryInterface extends RepositoryInterface
{

    public function create(NpcCreatePayload $payload): Generator;

    public function update(NpcUpdatePayload $payload): Generator;

    public function delete(IdPayload $payload): Generator;

    public function loadAll(): Generator;

}