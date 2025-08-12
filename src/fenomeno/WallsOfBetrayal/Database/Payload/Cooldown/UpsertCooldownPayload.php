<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Cooldown;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class UpsertCooldownPayload implements PayloadInterface
{

    public function __construct(
        public string $identifier,
        public string $type,
        public int    $expiry
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id'     => $this->identifier,
            'type'   => $this->type,
            'expiry' => $this->expiry
        ];
    }
}