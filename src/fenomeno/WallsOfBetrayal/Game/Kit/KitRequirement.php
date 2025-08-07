<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit;

use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;

class KitRequirement
{
    public function __construct(
        private readonly string             $type,
        private readonly KitRequirementType $target,
        private int                         $amount = 0
    ){}

    public function getType(): string { return $this->type; }
    public function getTarget(): KitRequirementType { return $this->target; }
    public function getAmount(): int { return $this->amount; }
    public function incrementAmount(): void
    {
        $this->amount++;
    }
}
