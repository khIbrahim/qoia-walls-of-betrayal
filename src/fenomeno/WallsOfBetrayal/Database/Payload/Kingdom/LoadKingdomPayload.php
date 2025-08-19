<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

readonly class LoadKingdomPayload implements PayloadInterface
{

    public function __construct(
        public string $kingdomId
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->kingdomId
        ];
    }
}