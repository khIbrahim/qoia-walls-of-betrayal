<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Player;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class LoadPlayerPayload implements PayloadInterface
{

    public function __construct(
        public string $uuid
    ){}

    public function jsonSerialize(): array
    {
        return ['uuid' => $this->uuid];
    }
}