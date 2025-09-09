<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

final readonly class SavePlayerInventoriesPayload extends UuidUsernamePayload
{

    public function __construct(
        ?string $uuid,
        ?string $username,
        public string $context,
        public string $inv,
        public string $armor,
        public string $offhand
    ){
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return [
            "uuid"      => $this->uuid,
            "name"      => $this->username,
            "context"   => $this->context,
            "inventory" => $this->inv,
            "armor"     => $this->armor,
            "offhand"   => $this->offhand
        ];
    }

}