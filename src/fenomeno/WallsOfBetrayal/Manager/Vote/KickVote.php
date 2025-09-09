<?php

namespace fenomeno\WallsOfBetrayal\Manager\Vote;

final class KickVote
{
    public function __construct(
        public int    $id,
        public string $kingdomId,
        public string $target,
        public string $reason,
        public int    $expiresAt,
        public int    $votesFor = 0,
        public int    $votesAgainst = 0
    ){}

    public function isExpired(int $now): bool
    {
        return $now >= $this->expiresAt;
    }
}


