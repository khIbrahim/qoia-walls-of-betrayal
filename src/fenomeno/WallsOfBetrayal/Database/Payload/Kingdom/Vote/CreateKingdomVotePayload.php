<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class CreateKingdomVotePayload implements PayloadInterface
{
    public function __construct(
        public string $kingdomId,
        public string $voteType,
        public string $target,
        public string $proposedBy,
        public string $reason,
        public int    $sanctionDuration,
        public int    $expiresAt
    ){}

    public function jsonSerialize(): array
    {
        return [
            'kingdom_id'        => $this->kingdomId,
            'vote_type'         => $this->voteType,
            'target'            => $this->target,
            'proposed_by'       => $this->proposedBy,
            'reason'            => $this->reason,
            'sanction_duration' => $this->sanctionDuration,
            'expires_at'        => $this->expiresAt
        ];
    }
}
