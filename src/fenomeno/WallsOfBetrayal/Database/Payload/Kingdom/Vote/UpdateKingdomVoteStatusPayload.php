<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class UpdateKingdomVoteStatusPayload implements PayloadInterface
{
    public function __construct(
        private int    $id,
        private string $status
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status
        ];
    }
}


