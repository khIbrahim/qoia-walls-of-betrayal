<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerRolesRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPermissionsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPlayerRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetSubRolesPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\InsertRolePlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdatePermissionsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdatePlayerRoleRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdateSubRolesPayload;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\DTO\RolePlayerDTO;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\MissingDataException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RolePlayerNotFoundException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

class PlayerRolesRepository implements PlayerRolesRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_ROLES, [], function (){
            $this->main->getLogger()->info("§aTable `player_roles` has been successfully init");
        });
    }

    public function load(GetPlayerRolePayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload) {
            $this->main->getDatabaseManager()->executeSelect(
                Statements::GET_PLAYER_ROLE,
                $payload->jsonSerialize(),
                function (array $rows) use ($resolve, $reject) {
                    if (empty($rows)) {
                        $resolve(null);
                        return;
                    }

                    $row = $rows[0];
                    if (!isset($row['role_id'])) {
                        $reject(new MissingDataException("Missing data in player role row"));
                        return;
                    }

                    $roleId = (string)$row['role_id'];
                    $assignedAt = (int)$row['assigned_at'];
                    $expiresAt = isset($row['expires_at']) ? (int)$row['expires_at'] : null;
                    $username = isset($row['username']) ? (string)$row['username'] : null;
                    $uuid = (string)$row['uuid'];
                    $subRoles = isset($row['subRoles']) ? json_decode($row['subRoles'], true) : [];
                    $permissions = isset($row['permissions']) ? json_decode($row['permissions'], true) : [];

                    $resolve(
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
                }, $reject
            );
        });
    }

    public function insert(InsertRolePlayerPayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncInsert(
            Statements::INSERT_PLAYER_ROLE,
            $payload->jsonSerialize(),
        );
    }

    public function updateRole(UpdatePlayerRoleRolePayload $payload): Generator
    {
        $affectedRows = yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_PLAYER_ROLE_ROLE, $payload->jsonSerialize());

        if ($affectedRows === 0) {
            throw new RolePlayerNotFoundException("Joueur " . ($payload->username ?? "null") . " n'existe pas");
        }
    }

    public function updatePermissions(UpdatePermissionsPayload $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
    {
        $this->main->getDatabaseManager()->executeChange(
            Statements::UPDATE_PLAYER_PERMISSIONS,
            $payload->jsonSerialize(),
            function (int $affectedRows) use ($onFailure, $onSuccess) {
                if ($affectedRows === 0){
                    if ($onFailure){
                        $onFailure(new RolePlayerNotFoundException());
                        return;
                    }
                }

                if ($onSuccess !== null) {
                    $onSuccess();
                }
            },
            function (Throwable $e) use ($onFailure) {
                if ($onFailure !== null) {
                    $onFailure(new DatabaseException(Statements::UPDATE_PLAYER_PERMISSIONS . " failed " . $e->getMessage(), $e->getCode(), $e));
                }
            }
        );
    }

    /**
     * @throws DatabaseException
     * @throws RolePlayerNotFoundException
     */
    public function getPermissions(GetPermissionsPayload $payload): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_PLAYER_PERMISSIONS, $payload->jsonSerialize());

        if (empty($rows)) {
            throw new RolePlayerNotFoundException();
        }

        $permissions = json_decode($rows[0]['permissions'] ?? '[]', true);

        if (! is_array($permissions)) {
            throw new DatabaseException("Invalid permissions data for player: " . $payload->username);
        }

        return $permissions;
    }

    /**
     * @throws DatabaseException
     * @throws RolePlayerNotFoundException
     */
    public function getSubRoles(GetSubRolesPayload $payload): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_PLAYER_SUBROLES, $payload->jsonSerialize());

        if (empty($rows)) {
            throw new RolePlayerNotFoundException();
        }

        $subRoles = json_decode($rows[0]['subRoles'] ?? '[]', true);

        if (! is_array($subRoles)) {
            throw new DatabaseException("Invalid sub roles data for player: " . $payload->username);
        }

        return $subRoles;
    }

    public function updateSubRoles(UpdateSubRolesPayload $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
    {
        $this->main->getDatabaseManager()->executeChange(
            Statements::UPDATE_PLAYER_SUBROLES,
            $payload->jsonSerialize(),
            function (int $affectedRows) use ($onFailure, $onSuccess) {
                if ($affectedRows === 0){
                    if ($onFailure){
                        $onFailure(new RolePlayerNotFoundException());
                        return;
                    }
                }

                if ($onSuccess !== null) {
                    $onSuccess();
                }
            },
            function (Throwable $e) use ($onFailure) {
                if ($onFailure !== null) {
                    $onFailure(new DatabaseException(Statements::UPDATE_PLAYER_SUBROLES . " failed " . $e->getMessage(), $e->getCode(), $e));
                }
            }
        );
    }
    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/roles.sql'
            ]
        ];
    }
}