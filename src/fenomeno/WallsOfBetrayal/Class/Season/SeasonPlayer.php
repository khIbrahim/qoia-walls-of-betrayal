<?php

namespace fenomeno\WallsOfBetrayal\Class\Season;

use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\Player\UpdateSeasonPlayerStats;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use Throwable;

class SeasonPlayer
{

    public function __construct(
        private readonly int    $id,
        private readonly int    $seasonId,
        private readonly string $playerUuid,
        private int             $kills = 0,
        private int             $deaths = 0,
        private int             $points = 0,
        private array           $rewards_claimed = [],
        private int             $createdAt = 0,
        private int             $updatedAt = 0
    ){}

    public static function fromArray(array $data): SeasonPlayer
    {
        return new self(
            id: $data['id'],
            seasonId: $data['season_id'],
            playerUuid: $data['player_uuid'],
            kills: (int) ($data['kills'] ?? 0),
            deaths: (int) ($data['deaths'] ?? 0),
            points: (int) ($data['points'] ?? 0),
            rewards_claimed: json_decode($data['rewards_claimed'] ?? '[]', true) ?: [],
            createdAt: (int) ($data['created_at'] ?? 0),
            updatedAt: (int) ($data['updated_at'] ?? 0)
        );
    }

    public function incrementKills(int $by = 1): void
    {
        $this->kills += $by;
    }

    public function incrementDeaths(int $by = 1): void
    {
        $this->deaths += $by;
    }

    public function flushStats(): Generator
    {
        try {
            yield from Main::getInstance()->getDatabaseManager()->getSeasonsRepository()->updatePlayerStats(new UpdateSeasonPlayerStats(
                seasonId: $this->seasonId,
                playerUuid: $this->playerUuid,
                kills: $this->kills,
                deaths: $this->deaths,
                points: $this->points
            ));
            return true;
        } catch (Throwable $e){
            Utils::onFailure($e, null, "Failed to flush season player stats");
            return false;
        }
    }

}