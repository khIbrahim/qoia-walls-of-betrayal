<?php

namespace fenomeno\WallsOfBetrayal\Class\Kingdom;

class KingdomBounty
{

    public function __construct(
        private readonly int    $id,
        private readonly string $kingdomId,
        private readonly string $targetPlayer,
        private readonly int    $amount,
        private readonly string $placedBy,
        private readonly int    $createdAt,
        private bool            $active = true,
        private ?string         $takenBy = null,
        private readonly bool   $strict = false
    )
    {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            kingdomId: (string)$row['kingdom_id'],
            targetPlayer: (string)$row['target_player'],
            amount: (int)$row['amount'],
            placedBy: (string)$row['placed_by'],
            createdAt: (int)$row['created_at'],
            active: isset($row['active']) && $row['active'],
            takenBy: $row['taken_by'] ?? null,
            strict: isset($row['strict']) && $row['strict']
        );
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getKingdomId(): string
    {
        return $this->kingdomId;
    }

    /**
     * @return string
     */
    public function getPlacedBy(): string
    {
        return $this->placedBy;
    }

    /**
     * @return string
     */
    public function getTargetPlayer(): string
    {
        return $this->targetPlayer;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return string|null
     */
    public function getTakenBy(): ?string
    {
        return $this->takenBy;
    }

    /**
     * @param string|null $takenBy
     */
    public function setTakenBy(?string $takenBy): void
    {
        $this->takenBy = $takenBy;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function canClaim(string $killer): bool
    {
        if (!$this->strict) {
            return true;
        }

        return strtolower($this->placedBy) === strtolower($killer);
    }

}