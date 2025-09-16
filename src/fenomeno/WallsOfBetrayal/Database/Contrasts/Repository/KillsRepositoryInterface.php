<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Logs\Domain\KillLogEvent;
use Generator;

interface KillsRepositoryInterface extends RepositoryInterface
{

    public function logKill(KillLogEvent $payload): Generator;

}