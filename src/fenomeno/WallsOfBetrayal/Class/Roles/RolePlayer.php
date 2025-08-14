<?php

namespace fenomeno\WallsOfBetrayal\Class\Roles;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;

class RolePlayer
{

    private ?PermissionAttachment $attachment = null;
    /** @var array<string,bool> */
    private array $effective = []; // toute les perms

    public function __construct(
        private string $role,
        private array $subRoles,
        private array $permissions = [], // perms perso
        private ?int $assignedAt = null,
        private ?int $expiresAt = null,
    ) {}

    public function getRoleId(): string{return $this->role;}
    public function getSubRoles(): array{return $this->subRoles;}
    public function getPermissions(): array{return $this->permissions;}
    public function getAssignedAt(): ?int{return $this->assignedAt;}
    public function getExpiresAt(): ?int{return $this->expiresAt;}
    public function setRoleId(string $role): void{$this->role = $role;}
    public function setSubRoles(array $subRoles): void{$this->subRoles = $subRoles;}
    public function setPermissions(array $permissions): void{$this->permissions = $permissions;}
    public function setAssignedAt(?int $assignedAt): void{$this->assignedAt = $assignedAt;}
    public function setExpiresAt(?int $expiresAt): void{$this->expiresAt = $expiresAt;}

    public function applyTo(Player $p): void {
        $att = $this->attachment ??= $p->addAttachment(Main::getInstance());

        $att->clearPermissions();

        $bag = $this->buildPermissionBag();

        foreach (array_keys($bag) as $node) {
            $att->setPermission($node, true);
        }

        $p->recalculatePermissions();
    }

    public function detachFrom(Player $p): void {
        if ($this->attachment !== null) { $p->removeAttachment($this->attachment); $this->attachment = null; }
    }

    public function setRole(Role|string $role): void
    {
        if($role instanceof Role){
            $role = $this->getRoleId();
        }

        $this->role = $role;
    }

    public function getRole(): ?Role
    {
        return Main::getInstance()->getRolesManager()->getRoleById($this->getRoleId());
    }

    private function buildPermissionBag(): array
    {
        $bag = [];

        foreach ($this->permissions as $n) {
            $bag[strtolower((string)$n)] = true;
        }

        $role = $this->getRole();
        if ($role !== null) {
            foreach ($role->getPermissions() as $n) {
                $bag[strtolower((string)$n)] = true;
            }
        }

        foreach ($this->getSubRoles() as $sub) {
            $subObj = $sub instanceof Role ? $sub : Main::getInstance()->getRolesManager()->getRoleById((string)$sub);
            if ($subObj !== null) {
                foreach ($subObj->getPermissions() as $n) {
                    $bag[strtolower((string)$n)] = true;
                }
            }
        }

        return $bag;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $currentTime = time();
        return $currentTime > $this->expiresAt;
    }

    /**
     * Check seulement les permissions personnelles
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function addPermission(string $permission): void
    {
        if (! in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
            $this->effective[strtolower($permission)] = true;
        }
    }

    public function removePermission(string $permission): void
    {
        $key = array_search($permission, $this->permissions, true);
        if ($key !== false) {
            unset($this->permissions[$key]);
            unset($this->effective[strtolower($permission)]);
        }
    }

    public function hasSubRole(Role|string $roleId): bool
    {
        if ($roleId instanceof Role) {
            $roleId = $roleId->getId();
        }

        return in_array($roleId, $this->subRoles, true);
    }

    public function addSubRole(Role|string $role): void
    {
        if ($role instanceof Role) {
            $role = $role->getId();
        }

        if (! in_array($role, $this->subRoles, true)) {
            $this->subRoles[] = $role;
        }
    }

    public function removeSubRole(Role|string $role): void
    {
        if ($role instanceof Role) {
            $role = $role->getId();
        }

        $key = array_search($role, $this->subRoles, true);
        if ($key !== false) {
            unset($this->subRoles[$key]);
        }
    }


}