<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Economy;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class SetEconomyPayload implements PayloadInterface
{

    public function __construct(
        public int     $amount,
//        public ?string $uuid,
        public string $username
    ){}

    public function jsonSerialize(): array
    {
        return [
//            'uuid'   => $this->uuid,
            'name'   => $this->username,
            'amount' => $this->amount
        ];
    }
}