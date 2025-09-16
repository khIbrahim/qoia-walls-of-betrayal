<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Loyalty;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class UpdatePlayerLoyaltyPayload implements PayloadInterface
{

    public function __construct(
        public string $uuid,
        public string $username,
        public string $kingdomId,
        public int    $loyaltyScore = 50,
        public int    $contributionsCount = 0,
        public int    $betrayalsCount = 0,
        public ?int   $lastBetrayal = null
    ){}

    public function jsonSerialize(): array
    {
        return [
            'uuid'                => $this->uuid,
            'username'            => $this->username,
            'kingdom_id'          => $this->kingdomId,
            'loyalty_score'       => $this->loyaltyScore,
            'contributions_count' => $this->contributionsCount,
            'betrayals_count'     => $this->betrayalsCount,
            'last_betrayal'       => $this->lastBetrayal
        ];
    }
}