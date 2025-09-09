<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;

final readonly class LoadPlayerInventoriesPayload extends UuidPayload
{

    public function __construct(
        string $uuid,
        public string $context
    ){
        parent::__construct($uuid);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            "context" => $this->context
        ]);
    }

}