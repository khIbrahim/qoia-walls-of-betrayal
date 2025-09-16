<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Loyalty;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class PlayerContributeLoyaltyPayload implements PayloadInterface
{

    public function __construct(
        public string $uuid,
        public string $username,
        public string $kingdomId
    ){}

    public function jsonSerialize(): array
    {
        return [
            'uuid'       => $this->uuid,
            'username'   => $this->username,
            'kingdom_id' => $this->kingdomId
        ];
    }
}