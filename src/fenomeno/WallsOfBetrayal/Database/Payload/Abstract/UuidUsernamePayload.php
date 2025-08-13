<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Abstract;

readonly class UuidUsernamePayload extends UuidPayload
{

    public function __construct(
        string $uuid,
        public string $username
    ) {
        parent::__construct($uuid);
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid'     => $this->uuid,
            'username' => $this->username
        ];
    }

}