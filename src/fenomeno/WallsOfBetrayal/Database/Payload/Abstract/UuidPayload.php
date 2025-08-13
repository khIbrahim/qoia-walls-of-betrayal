<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Abstract;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

abstract readonly class UuidPayload implements PayloadInterface
{

    public function __construct(
        public string $uuid
    ) {}

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->getUuid()
        ];
    }

}