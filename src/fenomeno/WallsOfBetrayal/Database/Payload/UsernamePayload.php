<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

readonly class UsernamePayload implements PayloadInterface
{

    public function __construct(
        public string $username
    ){}

    public function jsonSerialize(): array
    {
        return [
            'username' => $this->username
        ];
    }
}
