<?php

namespace fenomeno\WallsOfBetrayal\Roles;

use Closure;
use fenomeno\WallsOfBetrayal\Class\Roles\Role;
use fenomeno\WallsOfBetrayal\Class\Roles\RolePlayer;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\GetPlayerRolePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Roles\InsertRolePlayerPayload;
use fenomeno\WallsOfBetrayal\DTO\RolePlayerDTO;
use fenomeno\WallsOfBetrayal\Language\ExtraTags;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use Symfony\Component\Filesystem\Path;
use Throwable;

class RolesManager
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
        $filePath = Path::join($this->main->getDataFolder(), $fileName);
        $this->main->saveResource($fileName); // on remplace pas ici, si jamais je veux l'update.
        $this->config = new Config($filePath, Config::YAML);
        $this->loadRoles();

        $rolesNames = implode(", ", array_map(fn(Role $r) => $r->getDisplayName(), $this->roles));
        $this->main->getLogger()->info("§aROLES - Loaded " . count($this->roles) . " roles ($rolesNames)");

        $this->initDefaultRole();
        $this->initDefaultProps();
    }

    public function loadPlayer(string $uuid, string $username, Closure $onSuccess, Closure $onFailure): void
    {
        $this->main->getDatabaseManager()
            ->getRolesRepository()
            ->load(
                new GetPlayerRolePayload($uuid, $username),
                function (?RolePlayerDTO $rolePlayerDTO) use ($onSuccess, $username) {
                    if($rolePlayerDTO === null) {
                        $onSuccess(null);
                        return;
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
                    $onSuccess($rolePlayer);
                },
                $onFailure
            );
    }

    public function insertPlayer(string $uuid, string $username, Closure $onSuccess, Closure $onFailure): void
    {
        $username = $this->normalizeKey($username);

        $payload = new InsertRolePlayerPayload(
            uuid: $uuid,
            username: $username,
            roleId: $this->defaultRole->getId(),
            subRoles: $this->defaultRole->getSubRoles(),
            permissions: array_merge($this->defaultRole->getPermissions(), $this->defaultPermissions),
            expiresAt: null
        );

        $onSuccess = function () use ($payload, $username, $onSuccess) {
            $this->players[$username] = $rolePlayer = new RolePlayer(
                role: $payload->roleId,
                subRoles: $payload->subRoles,
                permissions: $payload->permissions,
                expiresAt: $payload->expiresAt,
            );
            $onSuccess($rolePlayer);
        };

        $this->main->getDatabaseManager()
            ->getRolesRepository()
            ->insert($payload, $onSuccess, $onFailure);
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

                $this->roles[$roleId] = new Role(
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
            } catch (Throwable $e){
                $this->main->getLogger()->error("§cFailed to load role $roleId: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        }
    }

    private function initDefaultRole(): void
    {
        // step 1 : check si un role est par défaut
        // step 2 : check si un role est défini dans la config
        // step 3 : check si on a des roles, sinon on log une erreur //TODO gérer les crash autour de ça en vrai elles sont juste dans l'insert
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

    public function getPlayer(string|Player $player): ?RolePlayer
    {
        $key = $this->normalizeKey($player);
        return $this->players[$key] ?? null;
    }

    public function formatChatMessage(Player $player, string $message): string
    {
        var_dump($this->getPlayer($player)?->getRole()?->getChatFormat());
        var_dump(($this->getPlayer($player)?->getRole()?->getChatFormat()) ?: $this->defaultChatFormat);
        return str_replace(
            [
                ExtraTags::COLOR,
                ExtraTags::ROLE,
                ExtraTags::PLAYER,
                ExtraTags::KINGDOM,
                ExtraTags::MESSAGE
            ],
            [
                $this->defaultRole->getColor(),
                $this->defaultRole->getDisplayName(),
                $player->getDisplayName(),
                (Session::get($player)->isLoaded() && Session::get($player)->getKingdom() !== null) ? Session::get($player)->getKingdom()->getDisplayName() : "No Kingdom",
                $message
            ],
            ($this->getPlayer($player)?->getRole()?->getChatFormat()) ?: $this->defaultChatFormat
        );
    }

    public function getRoleById(string $role): ?Role
    {
        return $this->roles[$role] ?? null;
    }

}