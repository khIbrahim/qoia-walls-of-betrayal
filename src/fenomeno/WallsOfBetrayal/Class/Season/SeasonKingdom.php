<?php

namespace fenomeno\WallsOfBetrayal\Class\Season;

use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\Kingdom\UpdateSeasonKingdomStats;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use Throwable;

class SeasonKingdom
{
    private bool $dirty = false;

    public function __construct(
        private readonly int    $id,
        private readonly int    $seasonId,
        private readonly string $kingdomId,
        private int             $points = 0,
        private int             $ranking = 0,
        private int             $wins = 0,
        private int             $losses = 0,
        private array           $rewardsClaimed = [],
        private int             $createdAt = 0,
        private int             $updatedAt = 0
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            seasonId: (int) $data['season_id'],
            kingdomId: (string) $data['kingdom_id'],
            points: (int) ($data['points'] ?? 0),
            ranking: (int) ($data['ranking'] ?? 0),
            wins: (int) ($data['wins'] ?? 0),
            losses: (int) ($data['losses'] ?? 0),
            rewardsClaimed: json_decode($data['rewards_claimed'] ?? '[]', true) ?: [],
            createdAt: (int) ($data['created_at'] ?? 0),
            updatedAt: (int) ($data['updated_at'] ?? 0)
        );
    }

    public function addPoints(int $points): void
    {
        $this->points += $points;
        $this->dirty = true;
    }

    public function addWin(): void
    {
        $this->wins++;
        $this->dirty = true;
    }

    public function addLoss(): void
    {
        $this->losses++;
        $this->dirty = true;
    }

    public function updateRanking(int $ranking): void
    {
        $this->ranking = $ranking;
        $this->dirty = true;
    }

    public function claimReward(string $rewardId): void
    {
        if (!in_array($rewardId, $this->rewardsClaimed, true)) {
            $this->rewardsClaimed[] = $rewardId;
            $this->dirty = true;
        }
    }

    public function hasClaimedReward(string $rewardId): bool
    {
        return in_array($rewardId, $this->rewardsClaimed, true);
    }

    public function flushStats(): Generator
    {
        if (!$this->dirty) {
            return false;
        }

        try {
            yield from Main::getInstance()->getDatabaseManager()->getSeasonsRepository()->updateKingdomStats(
                new UpdateSeasonKingdomStats(
                    seasonId: $this->seasonId,
                    kingdomId: $this->kingdomId,
                    points: $this->points,
                    ranking: $this->ranking,
                    wins: $this->wins,
                    losses: $this->losses,
                    rewardsClaimed: $this->rewardsClaimed
                )
            );

            $this->dirty = false;
            return true;
        } catch (Throwable $e) {
            Utils::onFailure($e, null, "Failed to flush season kingdom stats for kingdom: {$this->kingdomId}");
            return false;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSeasonId(): int
    {
        return $this->seasonId;
    }

    public function getKingdomId(): string
    {
        return $this->kingdomId;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function getRanking(): int
    {
        return $this->ranking;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function getWinRate(): float
    {
        $total = $this->wins + $this->losses;
        return $total > 0 ? ($this->wins / $total) * 100 : 0.0;
    }

    public function getRewardsClaimed(): array
    {
        return $this->rewardsClaimed;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'season_id'       => $this->seasonId,
            'kingdom_id'      => $this->kingdomId,
            'points'          => $this->points,
            'ranking'         => $this->ranking,
            'wins'            => $this->wins,
            'losses'          => $this->losses,
            'rewards_claimed' => $this->rewardsClaimed,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt
        ];
    }
}
