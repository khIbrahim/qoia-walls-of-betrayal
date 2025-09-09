<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

class UsernamePayload implements PayloadInterface
{

    protected string $usernameColumn = "username";

    public function __construct(
        public string $username
    ){}

    public function jsonSerialize(): array
    {
        return [
            $this->usernameColumn => $this->username
        ];
    }
}
