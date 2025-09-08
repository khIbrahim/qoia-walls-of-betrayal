<?php

namespace fenomeno\WallsOfBetrayal\DTO;

final readonly class VoteStatisticsDTO
{
    /**
     * @param int $totalMembers
     * @param int $totalVotes
     * @param float|int $participationPercent
     * @param int $votesFor
     * @param int $votesAgainst
     * @param float|int $forPercent
     * @param bool $isQuorumMet
     * @param bool $isMajorityMet
     * @param float $minimumParticipationPercent
     * @param float $majorityRequired
     * @param int $minVotesRequired
     */
    public function __construct(
        public int       $totalMembers,
        public int       $totalVotes,
        public float|int $participationPercent,
        public int       $votesFor,
        public int       $votesAgainst,
        public float|int $forPercent,
        public bool      $isQuorumMet,
        public bool      $isMajorityMet,
        public float     $minimumParticipationPercent,
        public float     $majorityRequired,
        public int       $minVotesRequired,
        public float|int $quorumPercent,
    )
    {
    }
}