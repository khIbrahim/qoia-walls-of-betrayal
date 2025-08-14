<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Roles;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

readonly class RemovePlayerRolePayload extends UuidUsernamePayload
{

    public function __construct(
        ?string $uuid,
        string $username,
        public string $roleId,
    ){
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            "role_id"    => $this->roleId,
        ]);
    }

}