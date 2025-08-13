<?php

namespace fenomeno\WallsOfBetrayal\DTO;

final readonly class RolePlayerDTO
{

    public function __construct(
        public string $uuid,
        public string $roleId,
        public int $assignedAt,
        public ?int $expiresAt = null,
        public ?string $username = null,
        public array $subRoles = [],
        public array $permissions = [],
    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < time();
    }

}