<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Seasons\Player;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class UpdateSeasonPlayerStats implements PayloadInterface
{

    public function __construct(
        public int    $seasonId,
        public string $playerUuid,
        public int    $kills,
        public int    $deaths,
        public int    $points
    ){}

    public function jsonSerialize(): array
    {
        return [
            'season_id'   => $this->seasonId,
            'player_uuid' => $this->playerUuid,
            'kills'       => $this->kills,
            'deaths'      => $this->deaths,
            'points'      => $this->points
        ];
    }
}