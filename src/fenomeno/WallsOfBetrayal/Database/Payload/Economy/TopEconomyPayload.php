<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Economy;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class TopEconomyPayload implements PayloadInterface
{

    public function __construct(
        public int $limit,
        public int $offset,
    ){}

    public function jsonSerialize(): array
    {
        return [
            'limit'  => $this->limit,
            'offset' => $this->offset,
        ];
    }
}