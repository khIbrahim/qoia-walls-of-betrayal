<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Economy;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class GetEconomyPayload implements PayloadInterface
{

    public function __construct(
        public ?string $username = null,
        public ?string $uuid     = null
    ){
        if($this->username === null && $this->uuid === null){
            throw new \InvalidArgumentException("Must provide username || uuid");
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->username
        ];
    }
}