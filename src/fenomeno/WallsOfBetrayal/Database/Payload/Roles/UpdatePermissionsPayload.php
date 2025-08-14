<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Roles;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

readonly class UpdatePermissionsPayload extends UuidUsernamePayload
{

    public function __construct(
        ?string $uuid,
        ?string $username,
        public array $permissions = [],
    ){
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'permissions' => json_encode($this->permissions),
        ]);
    }

}