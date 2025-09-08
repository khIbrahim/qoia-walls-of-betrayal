<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class UpdateKingdomVoteVotes implements PayloadInterface
{

    public function __construct(
        public int $voteId,
        public int $votesFor,
        public int $votesAgainst
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->voteId,
            'votesFor' => $this->votesFor,
            'votesAgainst' => $this->votesAgainst
        ];
    }
}