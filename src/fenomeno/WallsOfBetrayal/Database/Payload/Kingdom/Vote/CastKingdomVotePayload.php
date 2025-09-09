<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class CastKingdomVotePayload implements PayloadInterface
{
    public function __construct(
        private int    $voteId,
        private string $voterUuid,
        private string $voterName,
        private bool   $voteFor
    ){}

    public function jsonSerialize(): array
    {
        return [
            'vote_id'    => $this->voteId,
            'voter_uuid' => $this->voterUuid,
            'voter_name' => $this->voterName,
            'vote_for'   => (int) $this->voteFor
        ];
    }
}


