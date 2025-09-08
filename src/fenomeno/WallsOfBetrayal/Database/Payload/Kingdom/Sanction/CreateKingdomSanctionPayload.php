<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Sanction;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class CreateKingdomSanctionPayload implements PayloadInterface
{

    public function __construct(
        private string $kingdomId,
        private string $targetUuid,
        private string $targetName,
        private string $reason,
        private string $staff,
        private ?int   $expiresAt
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'kingdom_id' => $this->kingdomId,
            'uuid' => $this->targetUuid,
            'name' => $this->targetName,
            'reason' => $this->reason,
            'staff' => $this->staff,
            'expires' => $this->expiresAt
        ];
    }
}
