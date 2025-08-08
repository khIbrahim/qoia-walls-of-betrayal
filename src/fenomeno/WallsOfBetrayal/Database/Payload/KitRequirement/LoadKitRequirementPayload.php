<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class LoadKitRequirementPayload implements PayloadInterface
{

    public function __construct(
        private string $kingdomId,
        private string $kitId
    ){}

    public function jsonSerialize(): array
    {
        return [
            'kingdom' => $this->kingdomId,
            'kit'     => $this->kitId
        ];
    }
}