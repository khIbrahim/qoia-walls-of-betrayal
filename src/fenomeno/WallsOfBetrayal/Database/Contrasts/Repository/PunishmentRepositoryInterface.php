<?php
namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use Generator;

interface PunishmentRepositoryInterface extends RepositoryInterface
{

    public function getAll(): Generator;

    public function create(AbstractPunishment $punishment): Generator;

    public function delete(UsernamePayload $payload): Generator;

}