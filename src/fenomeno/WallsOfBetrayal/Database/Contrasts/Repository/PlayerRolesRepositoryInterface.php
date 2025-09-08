<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPermissionsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPlayerRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetSubRolesPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\InsertRolePlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdatePermissionsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdatePlayerRoleRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdateSubRolesPayload;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RolePlayerNotFoundException;
use Generator;

interface PlayerRolesRepositoryInterface extends RepositoryInterface
{

    public function load(GetPlayerRolePayload $payload): Generator;

    public function insert(InsertRolePlayerPayload $payload): Generator;

    /**
     * @throws RolePlayerNotFoundException
    */
    public function updateRole(UpdatePlayerRoleRolePayload $payload): Generator;

    public function updatePermissions(UpdatePermissionsPayload $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void;

    /**
     * @throws RolePlayerNotFoundException
     */
    public function getPermissions(GetPermissionsPayload $payload): Generator;

    public function getSubRoles(GetSubRolesPayload $payload): Generator;

    public function updateSubRoles(UpdateSubRolesPayload $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void;

}