<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

class CreateKingdomBountyPayload implements PayloadInterface
{

    public function __construct(
        public string $kingdomId,
        public string $target,
        public int    $amount,
        public string $placedBy,
        public bool   $strict = false
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'kingdom_id' => $this->kingdomId,
            'target_player' => $this->target,
            'amount' => $this->amount,
            'placed_by' => $this->placedBy,
            'strict' => (int)$this->strict
        ];
    }
}