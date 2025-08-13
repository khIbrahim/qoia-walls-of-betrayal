<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPlayerRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\InsertRolePlayerPayload;

interface RolesRepositoryInterface extends RepositoryInterface
{

    public function load(GetPlayerRolePayload $payload, Closure $onSuccess, Closure $onFailure): void;

    public function insert(InsertRolePlayerPayload $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void;

}