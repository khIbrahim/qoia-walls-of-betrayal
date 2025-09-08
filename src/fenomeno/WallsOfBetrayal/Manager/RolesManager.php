<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use Exception;
use fenomeno\WallsOfBetrayal\Class\Roles\Role;
use fenomeno\WallsOfBetrayal\Class\Roles\RolePlayer;
use fenomeno\WallsOfBetrayal\Commands\Arguments\RoleArgument;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPermissionsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPlayerRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetSubRolesPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\InsertRolePlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdatePermissionsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdatePlayerRoleRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\UpdateSubRolesPayload;
use fenomeno\WallsOfBetrayal\DTO\RolePlayerDTO;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerAlreadyHasPermissionException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerAlreadyHasRoleException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerAlreadyHasSubRoleException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerDontHasPermissionException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerDontHaveSubRoleException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RoleNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RolePlayerNotFoundException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Task\RolesTask;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use Generator;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use Symfony\Component\Filesystem\Path;
use Throwable;

final class RolesManager
{

    /** @var RolePlayer[] */
    private array $players = [];

    /** @var Role[] */
    private array $roles = [];
    private Config $config;

    private Role $defaultRole;
    private array $defaultPermissions = [];
    private string $defaultChatFormat;
    private string $defaultNameTag;

    public function __construct(private readonly Main $main){
        $fileName = 'roles.yml';
        $this->main->saveResource($fileName, true); // TODO on remplace pas ici, si jamais je veux l'update.
        $filePath = Path::join($this->main->getDataFolder(), $fileName);
        $this->config = new Config($filePath, Config::YAML);
        $this->loadRoles();

        $rolesNames = implode(", ", array_map(fn(Role $r) => $r->getDisplayName(), $this->roles));
        $this->main->getLogger()->info("§aROLES - Loaded " . count($this->roles) . " roles ($rolesNames)");

        $this->initDefaultRole();
        $this->initDefaultProps();

        $this->main->getScheduler()->scheduleDelayedRepeatingTask(new RolesTask($this->main), 20 * 5, (int) $this->config->getNested('parameters.nametag-task-tick', 20));
    }

    public function loadPlayer(?string $uuid, ?string $username, bool $insert = true): Generator
    {
        /** @var RolePlayerDTO $rolePlayerDTO */
        $rolePlayerDTO = yield from $this->main->getDatabaseManager()->getRolesRepository()->load(new GetPlayerRolePayload($uuid, $username));
        if ($rolePlayerDTO === null) {
            if ($insert) {
                return yield from $this->insertPlayer($uuid, $username);
            }

            return null;
        }

        $key = $this->normalizeKey($username);

        $rolePlayer = $this->players[$key] = new RolePlayer(
            role: $rolePlayerDTO->roleId,
            subRoles: $rolePlayerDTO->subRoles,
            permissions: $rolePlayerDTO->permissions,
            assignedAt: $rolePlayerDTO->assignedAt,
            expiresAt: $rolePlayerDTO->expiresAt,
        );

        $this->main->getLogger()->debug("§aROLES - Role Player $username loaded successfully.");

        return $rolePlayer;
    }

    public function getPlayer(string|Player $player): ?RolePlayer
    {
        $key = $this->normalizeKey($player);
        return $this->players[$key] ?? null;
    }

    public function insertPlayer(string $uuid, string $username): Generator
    {
        $username = $this->normalizeKey($username);

        $payload = new InsertRolePlayerPayload(
            uuid: $uuid,
            username: $username,
            roleId: $this->defaultRole->getId(),
            subRoles: $this->defaultRole->getInherits(),
            permissions: $this->defaultPermissions,
            expiresAt: null
        );

        yield from $this->main->getDatabaseManager()->getRolesRepository()->insert($payload);

        $this->players[$username] = $rolePlayer = new RolePlayer(
            role: $payload->roleId,
            subRoles: $payload->subRoles,
            permissions: $payload->permissions,
            assignedAt: time(),
            expiresAt: $payload->expiresAt
        );

        return $rolePlayer;
    }

    /**
     * @throws RoleNotFoundException
     * @throws RolePlayerNotFoundException
     * @throws PlayerAlreadyHasRoleException
     */
    public function setPlayerRole(Player|string $player, Role|string $role, ?int $expiresAt = null): Generator
    {
        $roleId = $role instanceof Role ? $role->getId() : $role;
        if (!isset($this->roles[$roleId])) {
            throw new RoleNotFoundException("Role with ID $roleId does not exist.");
        }

        $key = $this->normalizeKey($player);
        $rolePlayer = $this->getPlayer($player);
        $roleId = $role instanceof Role ? $role->getId() : $role;

        if ($rolePlayer) {
            if ($rolePlayer->getRole()->getId() === $roleId) {
                throw new PlayerAlreadyHasRoleException("Player " . (is_string($player) ? $player : $player->getName()) . " already has the role " . $role->getDisplayName());
            }

            $rolePlayer = clone $rolePlayer;
            $rolePlayer->setRole($roleId);
            $rolePlayer->setAssignedAt(time());
            $rolePlayer->setExpiresAt($expiresAt);
            unset($this->players[$key]);
            $this->players[$key] = $rolePlayer;

            if ($player instanceof Player) {
                $rolePlayer->applyTo($player);
            }
        }

        [$uuid, $username] = $player instanceof Player ? [$player->getUniqueId()->toString(), strtolower($player->getName())] : [null, strtolower($player)];

        yield from $this->main->getDatabaseManager()->getRolesRepository()->updateRole(
            new UpdatePlayerRoleRolePayload(
                uuid: $uuid,
                username: $username,
                roleId: $roleId,
                expiresAt: $expiresAt
            )
        );

        return $this->roles[$roleId];
    }

    /**
     * @throws
     */
    public function handleExpiredRole(Player|string $player): Generator
    {
        $fallbackRoleId = $this->defaultRole->getId();
        $rolePlayer = $this->getPlayer($player);
        if ($rolePlayer) {
            $subRoles = array_values($rolePlayer->getSubRoles());
            if (! empty($subRoles)) {
                $first = $subRoles[0];
                $fallbackRoleId = $first instanceof Role ? $first->getId() : (string)$first;
            }
        }

        return yield from $this->setPlayerRole($player, $fallbackRoleId);
    }

    /**
     * @throws PlayerAlreadyHasPermissionException
     * @throws RolePlayerNotFoundException
     */
    public function addPermission(string|Player $player, string $permission): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($permission, $player) {
            Await::f2c(function () use ($reject, $permission, $player, $resolve) {
                try {
                    $key = $this->normalizeKey($player);

                    if (isset($this->players[$key])) {
                        $rolePlayer = $this->getPlayer($player);
                        if ($rolePlayer->hasPermission($permission)){
                            $reject(new PlayerAlreadyHasPermissionException());
                            return;
                        }

                        $rolePlayer->addPermission($permission);
                        if ($player instanceof Player) {
                            $rolePlayer->applyTo($player);
                        }

                        $this->players[$key] = $rolePlayer;
                        $permissions = $rolePlayer->getPermissions();
                    } else {
                        $permissions = yield from $this->main->getDatabaseManager()
                            ->getRolesRepository()
                            ->getPermissions(new GetPermissionsPayload(
                                uuid: $player instanceof Player ? $player->getUniqueId()->toString() : null,
                                username: $key
                            ));

                        if (in_array($permission, $permissions, true)) {
                            $reject(new PlayerAlreadyHasPermissionException());
                            return;
                        }

                        $permissions[] = $permission;
                    }

                    [$uuid, $username] = $player instanceof Player ? [$player->getUniqueId()->toString(), strtolower($player->getName())] : [null, strtolower((string)$player)];

                    $this->main->getDatabaseManager()
                        ->getRolesRepository()
                        ->updatePermissions(
                            new UpdatePermissionsPayload(
                                uuid: $uuid,
                                username: $username,
                                permissions: array_values($permissions)
                            ),
                            function () use ($username, $resolve) {
                                //double apply:
                                $p = $this->main->getServer()->getPlayerExact($username);
                                if($p instanceof Player) {
                                    $rolePlayer = $this->getPlayer($p);
                                    $rolePlayer?->applyTo($p);
                                }
                                $resolve();
                            },
                            function (Throwable $e) use ($reject, $player) {
                                $reject(new Exception("Failed to add permission for player " . (is_string($player) ? $player : $player->getName()) . ": " . $e->getMessage()));
                            }
                        );
                    } catch (Throwable $e){
                        $reject(new DatabaseException($e->getMessage(), $e->getCode(), $e));
                    }
                }
            );
        });
    }

    public function removePermission(string|Player $player, string $permission): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($permission, $player) {
            Await::f2c(function () use ($reject, $permission, $player, $resolve) {
                try {
                    $key = $this->normalizeKey($player);
                    if (isset($this->players[$key])) {
                        $rolePlayer  = $this->getPlayer($player);
                        $permissions = $rolePlayer->getPermissions();
                        if (! $rolePlayer->hasPermission($permission)) {
                            throw new PlayerDontHasPermissionException();
                        }

                        $rolePlayer->removePermission($permission);
                        if ($player instanceof Player) {
                            $rolePlayer->applyTo($player);
                        }
                        $this->players[$key] = $rolePlayer;
                    } else {
                        $permissions = yield from $this->main->getDatabaseManager()
                            ->getRolesRepository()
                            ->getPermissions(new GetPermissionsPayload(
                                uuid: $player instanceof Player ? $player->getUniqueId()->toString() : null,
                                username: $key
                            ));

                        if (! in_array($permission, $permissions, true)) {
                            throw new PlayerDontHasPermissionException();
                        }

                    }

                    unset($permissions[array_search($permission, $permissions, true)]);
                    [$uuid, $username] = $player instanceof Player ? [$player->getUniqueId()->toString(), strtolower($player->getName())] : [null, strtolower((string)$player)];
                    $this->main->getDatabaseManager()
                        ->getRolesRepository()
                        ->updatePermissions(
                            new UpdatePermissionsPayload(
                                uuid: $uuid,
                                username: $username,
                                permissions: array_values($permissions)
                            ),
                            function () use ($username, $resolve) {
                                //double apply
                                $p = $this->main->getServer()->getPlayerExact($username);
                                if($p instanceof Player) {
                                    $rolePlayer = $this->getPlayer($p);
                                    $rolePlayer?->applyTo($p);
                                }
                                $resolve();
                            },
                            function (Throwable $e) use ($reject, $player) {
                                $reject(new DatabaseException("Failed to remove permission for player " . (is_string($player) ? $player : $player->getName()) . ": " . $e->getMessage()));
                                $this->main->getLogger()->logException($e);
                            }
                        );
                } catch (Throwable $e){
                    $reject($e);
                }
            });
        });

    }

    public function addSubRole(string|Player $player, Role|false $role): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player, $role) {
            Await::f2c(function () use ($reject, $role, $player, $resolve) {
                try {
                    $key = $this->normalizeKey($player);
                    $roleId = $role instanceof Role ? $role->getId() : $role;

                    if (isset($this->players[$key])) {
                        $rolePlayer = $this->getPlayer($key);
                        if ($rolePlayer->hasSubRole($role)){
                            $reject(new PlayerAlreadyHasSubRoleException());
                            return;
                        }

                        $rolePlayer->addSubRole($role);
                        if ($player instanceof Player) {
                            $rolePlayer->applyTo($player);
                        }

                        $this->players[$key] = $rolePlayer;
                        $subRoles = $rolePlayer->getSubRoles();
                    } else {
                        $subRoles = yield from $this->main->getDatabaseManager()
                            ->getRolesRepository()
                            ->getSubRoles(new GetSubRolesPayload(
                                uuid: $player instanceof Player ? $player->getUniqueId()->toString() : null,
                                username: $key
                            ));

                        if (in_array($roleId, $subRoles, true)) {
                            $reject(new PlayerAlreadyHasSubRoleException());
                            return;
                        }

                        $subRoles[] = $roleId;
                    }

                    [$uuid, $username] = $player instanceof Player ? [$player->getUniqueId()->toString(), strtolower($player->getName())] : [null, strtolower((string)$player)];
                    $this->main->getDatabaseManager()
                        ->getRolesRepository()
                        ->updateSubRoles(
                            new UpdateSubRolesPayload(
                                uuid: $uuid,
                                username: $username,
                                subRoles: array_values($subRoles)
                            ),
                            function () use ($resolve) {
                                $resolve();
                            },
                            function (Throwable $e) use ($reject, $player) {
                                $reject(new Exception("Failed to add subrole for player " . (is_string($player) ? $player : $player->getName()) . ": " . $e->getMessage()));
                            }
                        );
                } catch (Throwable $e){
                    $reject($e);
                }
            }
            );
        });
    }

    public function removeSubRole(string|Player $player, Role|false $role): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player, $role) {
            Await::f2c(function () use ($reject, $role, $player, $resolve) {
                try {
                    $key = $this->normalizeKey($player);
                    $roleId = $role instanceof Role ? $role->getId() : $role;

                    if (isset($this->players[$key])) {
                        $rolePlayer = $this->getPlayer($key);
                        if (! $rolePlayer->hasSubRole($role)){
                            throw new PlayerDontHaveSubRoleException();
                        }

                        $rolePlayer->removeSubRole($role);
                        if ($player instanceof Player) {
                            $rolePlayer->applyTo($player);
                        }

                        $this->players[$key] = $rolePlayer;
                        $subRoles = $rolePlayer->getSubRoles();
                    } else {
                        $subRoles = yield from $this->main->getDatabaseManager()
                            ->getRolesRepository()
                            ->getSubRoles(new GetSubRolesPayload(
                                uuid: $player instanceof Player ? $player->getUniqueId()->toString() : null,
                                username: $key
                            ));

                        if (! in_array($roleId, $subRoles, true)) {
                            throw new PlayerDontHaveSubRoleException();
                        }

                        unset($subRoles[array_search($roleId, $subRoles, true)]);
                    }

                    [$uuid, $username] = $player instanceof Player ? [$player->getUniqueId()->toString(), strtolower($player->getName())] : [null, strtolower((string)$player)];
                    $this->main->getDatabaseManager()
                        ->getRolesRepository()
                        ->updateSubRoles(
                            new UpdateSubRolesPayload(
                                uuid: $uuid,
                                username: $username,
                                subRoles: array_values($subRoles)
                            ),
                            function () use ($resolve) { $resolve(); },
                            function (Throwable $e) use ($reject, $player) {
                                $reject(new Exception("Failed to remove subrole for player " . (is_string($player) ? $player : $player->getName()) . ": " . $e->getMessage()));
                            }
                        );
                } catch (Throwable $e){
                    $reject($e);
                }
            });
        });
    }

    private function normalizeKey(string|Player $player): string
    {
        return strtolower($player instanceof Player ? $player->getName() : $player);
    }

    private function loadRoles(): void
    {
        $data = $this->config->get("roles", []);

        foreach ($data as $roleId => $roleData){
            try {
                if (! isset($roleData['displayName'], $roleData['color'], $roleData['permissions'], $roleData['chatFormat'], $roleData['nameTagFormat'])){
                    $this->main->getLogger()->warning("§cRole $roleId is missing required fields, skipping.");
                    continue;
                }

                $displayName   = (string) $roleData['displayName'];
                $color         = (string) $roleData['color'];
                $permissions   = (array) $roleData['permissions'];
                $chatFormat    = (string) $roleData['chatFormat'];
                $nameTagFormat = (string) $roleData['nameTagFormat'];
                $icon          = (string) ($roleData['icon'] ?? "");
                $heritages     = (array) ($roleData['inherits'] ?? []);
                $default       = (bool) ($roleData['default'] ?? false);

                $this->roles[$roleId] = $role = new Role(
                    id: $roleId,
                    displayName: $displayName,
                    permissions: $permissions,
                    heritages: $heritages,
                    chatFormat: $chatFormat,
                    nameTagFormat: $nameTagFormat,
                    icon: $icon,
                    color: $color,
                    default: $default
                );
                RoleArgument::$VALUES[$roleId] = $role;
            } catch (Throwable $e){
                $this->main->getLogger()->error("§cFailed to load role $roleId: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        }
    }

    private function initDefaultRole(): void
    {
        foreach ($this->roles as $role) {
            if ($role->isDefault()) {
                $this->defaultRole = $role;
                $this->main->getLogger()->info("§aDefault role set to {$this->defaultRole->getDisplayName()} found on role props.");
                return;
            }
        }

        if ($this->config->exists('defaultRole')) {
            $defaultRoleId = $this->config->get('defaultRole');
            if (isset($this->roles[$defaultRoleId])) {
                $this->defaultRole = $this->roles[$defaultRoleId];
                $this->main->getLogger()->warning("§cNo default role found, using {$this->defaultRole->getDisplayName()} as default from config.");
                return;
            }
        }

        if (! empty($this->roles)) {
            $this->defaultRole = array_values($this->roles)[0];
            $this->main->getLogger()->warning("§cNo default role found, using {$this->defaultRole->getDisplayName()} as default found on the first element on roles.");
        } else {
            $this->main->getLogger()->error("§cNo roles found, cannot set a default role.");
        }
    }

    private function initDefaultProps(): void
    {
        $defaultPermissions = (array) $this->config->getNested('defaults.permissions', []);
        $this->defaultPermissions = $defaultPermissions;
        $this->main->getLogger()->info("§aDefault permissions (" . count($this->defaultPermissions) . ") loaded from config defaults.permissions");

        $this->defaultChatFormat = (string) $this->config->getNested('defaults.chatFormat', "{RANK_COLOR}{RANK} {USERNAME}: {MESSAGE}");
        $this->defaultNameTag = (string) $this->config->getNested('defaults.nameTagFormat', "{RANK_COLOR}{RANK} {USERNAME}");
    }

    private function preparePlayerData(Player $player): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player) {
            $rolePlayer = $this->getPlayer($player);
            if (!$rolePlayer) {
                $reject(new Exception("RolePlayer not found for player " . $player->getName()));
                return;
            }

            $role = $rolePlayer->getRole();
            if (!$role) {
                $reject(new Exception("Role not found for role ID for " . $player->getName()));
                return;
            }

            $session = Session::get($player);
            if (! $session->isLoaded()) {
                $reject(new Exception("Session not loaded or kingdom not found for player " . $player->getName()));
                return;
            }

            $kingdom = $session->getKingdom() !== null ? $session->getKingdom()->getDisplayName() : "No Kingdom";

            $resolve([
                'role' => $role,
                'kingdom' => $kingdom,
            ]);
        });
    }

    public function formatNameTag(Player $player): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player) {
            Await::g2c(
                $this->preparePlayerData($player),
                function (array $data) use ($resolve, $player) {
                    $formattedNameTag = str_replace(
                        [
                            ExtraTags::COLOR,
                            ExtraTags::ROLE,
                            ExtraTags::PLAYER,
                            ExtraTags::KINGDOM
                        ],
                        [
                            $data['role']->getColor(),
                            $data['role']->getDisplayName(),
                            $player->getDisplayName(),
                            $data['kingdom']
                        ],
                        $data['role']->getNameTagFormat() ?: $this->defaultNameTag
                    );

                    $resolve($formattedNameTag);
                },
                function (Throwable $e) use ($reject, $player) {
                    $reject(new Exception("Failed to prepare player data for name tag: " . $e->getMessage()));
                }
            );
        });
    }

    public function formatChatMessage(Player $player, string $message): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player, $message) {
            Await::g2c(
                $this->preparePlayerData($player),
                function (array $data) use ($resolve, $player, $message) {
                    $formattedMessage = str_replace(
                        [
                            ExtraTags::COLOR,
                            ExtraTags::ROLE,
                            ExtraTags::PLAYER,
                            ExtraTags::KINGDOM,
                            ExtraTags::MESSAGE
                        ],
                        [
                            $data['role']->getColor(),
                            $data['role']->getDisplayName(),
                            $player->getDisplayName(),
                            $data['kingdom'],
                            $message
                        ],
                        $data['role']->getChatFormat() ?: $this->defaultChatFormat
                    );

                    $resolve($formattedMessage);
                },
                function (Throwable $e) use ($reject, $player) {
                    $reject(new Exception("Failed to prepare player data for chat message: " . $e->getMessage()));
                }
            );
        });
    }

    public function getRoleById(string $role): ?Role
    {
        return $this->roles[$role] ?? null;
    }

    /** @return string[] -> roleName */
    public function getRolesNames(): array
    {
        return array_map(fn(Role $role) => $role->getDisplayName(), $this->roles);
    }

    /** @return Role[] */
    public function getRoles(): array
    {
        return $this->roles;
    }

}