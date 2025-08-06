<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Player;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class SetPlayerKingdomPayload implements PayloadInterface
{

    public function __construct(
        private string $uuid,
        private string $kingdomId
    ){}

    public function jsonSerialize(): array
    {
        return [
            'uuid'    => $this->uuid,
            'kingdom' => $this->kingdomId
        ];
    }
}