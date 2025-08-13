<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Roles;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

readonly class InsertRolePlayerPayload extends UuidUsernamePayload
{

    public function __construct(
        string        $uuid,
        string        $username,
        public string $roleId,
        public array  $subRoles = [],
        public array  $permissions = [],
        public ?int   $expiresAt = null
    ){
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'role_id'      => $this->roleId,
            'subRoles'     => json_encode($this->subRoles),
            'permissions'  => json_encode($this->permissions),
            'expires_at'   => $this->expiresAt,
        ]);
    }

}