<?php

namespace fenomeno\WallsOfBetrayal\Class\Player;

use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyRank;

class PlayerLoyalty
{

    public function __construct(
        public string $uuid,
        public string $username,
        public string $kingdomId,
        public int    $score = 0,
        public ?int   $lastContribution = null,
        public int    $contributionCount = 0,
        public int    $betrayalCount = 0,
        public ?int   $lastBetrayal = null
    ){}

    public static function fromArray(array $data): PlayerLoyalty
    {
        return new PlayerLoyalty(
            uuid: $data['uuid'],
            username: $data['username'],
            kingdomId: $data['kingdom_id'],
            score: (int) ($data['loyalty_score'] ?? 0),
            lastContribution: isset($data['last_contribution']) ? (int)$data['last_contribution'] : null,
            contributionCount: (int) ($data['contribution_count'] ?? 0),
            betrayalCount: (int) ($data['betrayal_count'] ?? 0),
            lastBetrayal: isset($data['last_betrayal']) ? (int) $data['last_betrayal'] : null
        );
    }

    public function addScore(int $score = 1): void
    {
        $this->score += $score;
        if ($this->score < 0) {
            $this->score = 0;
        }
    }

    public function getLoyaltyRank(): LoyaltyRank
    {
        return LoyaltyRank::fromScore($this->score);
    }

}