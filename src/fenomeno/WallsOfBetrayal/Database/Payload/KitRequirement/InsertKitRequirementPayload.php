<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class InsertKitRequirementPayload implements PayloadInterface
{

    /**
     * @param int $id
     * @param string $kingdomId
     * @param string $kitId
     */
    public function __construct(
        private int $id,
        private string $kingdomId,
        private string $kitId,
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id'      => $this->id,
            'kingdom' => $this->kingdomId,
            'kit'     => $this->kitId
        ];
    }
}