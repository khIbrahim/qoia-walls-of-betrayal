<?php

namespace fenomeno\WallsOfBetrayal\Class\Roles;

use fenomeno\WallsOfBetrayal\Main;

/**
 * Une classe pour le joueur afin de gérer son rôle, ses permissions, etc...
 */
class RolePlayer
{
    public function __construct(
        private string $role,
        private array $subRoles,
        private array $permissions = [],
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

    public function getRole(): ?Role
    {
        return Main::getInstance()->getRolesManager()->getRoleById($this->getRoleId());
    }


}