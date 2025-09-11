<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Seasons\Kingdom;

use JsonSerializable;

class UpdateSeasonKingdomStats implements JsonSerializable
{
    public function __construct(
        public readonly int    $seasonId,
        public readonly string $kingdomId,
        public readonly int    $points,
        public readonly int    $ranking,
        public readonly int    $wins,
        public readonly int    $losses,
        public readonly array  $rewardsClaimed
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'season_id'       => $this->seasonId,
            'kingdom_id'      => $this->kingdomId,
            'points'          => $this->points,
            'ranking'         => $this->ranking,
            'wins'            => $this->wins,
            'losses'          => $this->losses,
            'rewards_claimed' => json_encode($this->rewardsClaimed)
        ];
    }
}
