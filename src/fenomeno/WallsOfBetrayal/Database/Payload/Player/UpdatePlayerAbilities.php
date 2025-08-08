<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Player;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class UpdatePlayerAbilities implements PayloadInterface
{

    public function __construct(
        private string $uuid,
        private array  $abilities
    ){}

    public function jsonSerialize(): array
    {
        return [
            'uuid'      => $this->uuid,
            'abilities' => json_encode($this->abilities),
        ];
    }
}