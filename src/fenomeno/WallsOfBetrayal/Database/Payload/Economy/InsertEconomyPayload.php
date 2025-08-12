<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Economy;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final class InsertEconomyPayload implements PayloadInterface
{

    public function __construct(
        public string $username,
        public string $uuid
    ){}

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->username
        ];
    }
}