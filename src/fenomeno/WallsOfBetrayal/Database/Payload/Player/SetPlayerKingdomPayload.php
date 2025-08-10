<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Player;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class SetPlayerKingdomPayload implements PayloadInterface
{

    public function __construct(
        private string $uuid,
        private string $name,
        private string $kingdomId,
        private array  $abilities
    ){}

    public function jsonSerialize(): array
    {
        return [
            'uuid'      => $this->uuid,
            'name'      => $this->name,
            'kingdom'   => $this->kingdomId,
            'abilities' => json_encode($this->abilities)
        ];
    }
}