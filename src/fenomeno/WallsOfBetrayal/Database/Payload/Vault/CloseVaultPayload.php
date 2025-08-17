<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Vault;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

readonly class CloseVaultPayload extends UuidUsernamePayload
{

    public function __construct(
        ?string $uuid,
        ?string $username,
        public string $contents,
        public int $number = 1,
    ){
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            "number" => $this->number,
            "items"  => $this->contents
        ]);
    }

}