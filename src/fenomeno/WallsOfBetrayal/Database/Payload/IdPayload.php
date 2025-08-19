<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

readonly class IdPayload implements PayloadInterface
{

    public function __construct(
        public string $id
    ){}

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id
        ];
    }
}