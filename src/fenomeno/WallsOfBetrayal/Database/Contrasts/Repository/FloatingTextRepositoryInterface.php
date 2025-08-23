<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\FloatingText\CreateFloatingTextPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\FloatingText\UpdateFloatingTextPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use Generator;

interface FloatingTextRepositoryInterface extends RepositoryInterface
{

    public function load(Closure $onSuccess, Closure $onFailure): void;

    public function create(CreateFloatingTextPayload $payload): Generator;

    public function remove(IdPayload $payload): Generator;

    public function updateText(UpdateFloatingTextPayload $payload): Generator;

}