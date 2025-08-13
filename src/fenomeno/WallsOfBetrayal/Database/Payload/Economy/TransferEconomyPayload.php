<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Economy;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class TransferEconomyPayload implements PayloadInterface
{

    public function __construct(
        public string $senderUuid,
        public string $senderUsername,
        public string $receiverUuid,
        public string $receiverUsername,
        public int    $amount
    ){}

    public function jsonSerialize(): array
    {
        return [
            'r_uuid' => $this->receiverUuid,
            'r_name' => $this->receiverUsername,
            's_uuid' => $this->senderUuid,
            's_name' => $this->senderUsername,
            'amount' => $this->amount
        ];
    }
}