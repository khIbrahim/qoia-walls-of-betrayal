<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\RolesRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPlayerRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\InsertRolePlayerPayload;
use fenomeno\WallsOfBetrayal\DTO\RolePlayerDTO;
use fenomeno\WallsOfBetrayal\Exceptions\MissingDataException;
use fenomeno\WallsOfBetrayal\Main;
use Throwable;

/**
 * On va faire un truc tarpin simple ici, c'est simple pour un system de roles car ça ne change pas souvent.
 */
class RolesRepository implements RolesRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_ROLES, [], function (){
            $this->main->getLogger()->info("§aTable `player_roles` has been successfully init");
        });
    }

    public function load(GetPlayerRolePayload $payload, Closure $onSuccess, Closure $onFailure): void
    {
        $this->main->getDatabaseManager()->executeSelect(
            Statements::GET_PLAYER_ROLE,
            $payload->jsonSerialize(),
            function (array $rows) use ($onFailure, $onSuccess) {
                if (empty($rows)){
                    $onSuccess(null);
                    return;
                }

                $row = $rows[0];
                if(! isset($row['role_id'], $row['assigned_at'])){
                    $onFailure(new MissingDataException("Missing data in player role row"));
                    return;
                }

                $roleId      = (string) $row['role_id'];
                $assignedAt  = (int) $row['assigned_at'];
                $expiresAt   = isset($row['expires_at']) ? (int) $row['expires_at'] : null;
                $username    = isset($row['username']) ? (string) $row['username'] : null;
                $uuid        = (string) $row['uuid'];
                $subRoles    = isset($row['subRoles']) ? json_decode($row['subRoles'], true) : [];
                $permissions = isset($row['permissions']) ? json_decode($row['permissions'], true) : [];

                $onSuccess(
                    new RolePlayerDTO(
                        uuid: $uuid,
                        roleId: $roleId,
                        assignedAt: $assignedAt,
                        expiresAt: $expiresAt,
                        username: $username,
                        subRoles: $subRoles,
                        permissions: $permissions
                    )
                );
            }, function (Throwable $e) use ($onFailure) {
                $this->main->getLogger()->error("Failed to get player role: " . $e->getMessage());
                $onFailure($e);
            }
        );
    }

    public function insert(InsertRolePlayerPayload $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
    {
        $this->main->getDatabaseManager()->executeInsert(
            Statements::INSERT_PLAYER_ROLE,
            $payload->jsonSerialize(),
            $onSuccess,
            $onFailure
        );
    }

}