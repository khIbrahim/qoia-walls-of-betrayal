<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit;

use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;

class KitRequirement
{
    public function __construct(
        private readonly KitRequirementType $type,
        private readonly mixed              $target,
        private readonly int                $amount,
        private int                         $progress = 0
    ){}

    public function getType(): KitRequirementType { return $this->type; }
    public function getTarget(): mixed { return $this->target; }
    public function getAmount(): int { return $this->amount; }
    public function getProgress(): int { return $this->progress; }
    public function incrementProgress(): void
    {
        $this->progress++;
    }

    public function isComplete(): bool
    {
        return $this->progress >= $this->amount;
    }
}
