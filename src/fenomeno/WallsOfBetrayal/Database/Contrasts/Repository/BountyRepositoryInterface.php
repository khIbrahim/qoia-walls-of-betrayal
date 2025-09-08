<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\CreateKingdomBountyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\DeactivateBountyPayload;
use Generator;

interface BountyRepositoryInterface extends RepositoryInterface
{

    public function loadActives(Closure $onSuccess, Closure $onFailure): void;

    public function create(CreateKingdomBountyPayload $payload): Generator;

    public function deactivate(DeactivateBountyPayload $payload): Generator;
}