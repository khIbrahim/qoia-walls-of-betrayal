<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Roles;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

readonly class UpdatePlayerRoleRolePayload extends UuidUsernamePayload
{

    public function __construct(
        ?string $uuid,
        ?string $username,
        public string $roleId,
        public ?int $expiresAt = null
    ){
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            "role_id"    => $this->roleId,
            "expires_at" => $this->expiresAt,
        ]);
    }

}