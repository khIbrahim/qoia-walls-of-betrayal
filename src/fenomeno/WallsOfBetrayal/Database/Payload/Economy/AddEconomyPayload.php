<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Economy;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

class AddEconomyPayload implements PayloadInterface
{

    public function __construct(
        public int     $amount,
        public ?string $username = null,
        public ?string $uuid = null,
    ){
        if($this->username === null && $this->uuid === null){
            throw new \InvalidArgumentException("Must provide username || uuid");
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid'   => $this->uuid,
            'name'   => $this->username,
            'amount' => $this->amount
        ];
    }
}