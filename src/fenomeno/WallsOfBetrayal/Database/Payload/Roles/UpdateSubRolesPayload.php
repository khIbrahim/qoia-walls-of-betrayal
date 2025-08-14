<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Roles;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

readonly class UpdateSubRolesPayload extends UuidUsernamePayload {

    public function __construct(
        ?string $uuid,
        ?string $username,
        private array $subRoles
    ) {
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'subRoles' => json_encode($this->subRoles)
        ]);
    }
}