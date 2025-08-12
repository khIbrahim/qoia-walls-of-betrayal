<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Cooldown;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class RemoveCooldownPayload implements PayloadInterface
{

    public function __construct(
        private string $type,
        private string $identifier,
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id'   => $this->identifier,
            'type' => $this->type
        ];
    }
}