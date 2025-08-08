<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class IncrementKitRequirementPayload implements PayloadInterface
{

    public function __construct(
        private int $id,
        private string $kingdomId,
        private string $kitId,
        private int $progress,
        private int $max
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id'       => $this->id,
            'kingdom'  => $this->kingdomId,
            'kit'      => $this->kitId,
            'progress' => $this->progress,
            'max'      => $this->max
        ];
    }
}