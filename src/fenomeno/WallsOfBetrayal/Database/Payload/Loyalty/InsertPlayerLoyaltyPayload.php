<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Loyalty;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

final readonly class InsertPlayerLoyaltyPayload extends UuidUsernamePayload
{

    public function __construct(
        string $uuid,
        string $username,
        public string $kingdomId
    ){
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'kingdom_id' => $this->kingdomId
        ]);
    }

}