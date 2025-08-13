<?php

namespace fenomeno\WallsOfBetrayal\Class\Roles;

class Role
{

    public function __construct(
        private readonly string $id,
        private string          $displayName,
        private array           $permissions = [],
        private array           $heritages = [],
        private string          $chatFormat = "",
        private string          $nameTagFormat = "",
        private string          $icon = "",
        private string          $color = "",
        private bool            $default = false
    ){}

    public function getDisplayName(): string {return $this->displayName;}
    public function isDefault(): bool{return $this->default;}
    public function getId(): string {return $this->id;}
    public function getSubRoles(): array{return $this->heritages;}
    public function getPermissions(): array {return $this->permissions;}
    public function getChatFormat(): string {return $this->chatFormat;}
    public function getNameTagFormat(): string {return $this->nameTagFormat;}
    public function getIcon(): string {return $this->icon;}
    public function getColor(): string {return $this->color;}

}