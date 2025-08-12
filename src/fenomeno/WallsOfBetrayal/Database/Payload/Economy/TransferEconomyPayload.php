<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Economy;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class TransferEconomyPayload implements PayloadInterface
{

    public function __construct(
        public string $senderUuid,
        public string $receiverUuid,
        public int    $amount
    ){}

    public function jsonSerialize(): array
    {
        return [
            'r_uuid' => $this->receiverUuid,
            's_uuid' => $this->senderUuid,
            'amount' => $this->amount
        ];
    }
}