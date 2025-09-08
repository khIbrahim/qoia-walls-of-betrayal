<?php

namespace fenomeno\WallsOfBetrayal\Manager\Vote\Handlers;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use Generator;

interface VoteHandlerInterface
{

    public function handle(KingdomVote $vote): Generator;

    public function getType(): KingdomVoteType;

}