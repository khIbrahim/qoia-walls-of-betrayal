<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Manager\Vote;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Config\KingdomVotingConfig;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\DTO\VoteStatisticsDTO;

final class VoteQuorumManager
{
    private KingdomVotingConfig $config;

    public function __construct(Main $main)
    {
        $this->config = new KingdomVotingConfig($main);
    }

    public function isQuorumMet(Kingdom $kingdom, KingdomVote $vote): bool
    {
        $totalMembers = $this->getTotalMembers($kingdom);
        $totalVotes = $vote->votesFor + $vote->votesAgainst;

        if ($totalVotes < $this->config->getMinimumVotesRequired()) {
            return false;
        }

        $participationPercent = ($totalVotes / $totalMembers) * 100;
        if ($participationPercent < $this->config->getMinimumParticipationPercent()) {
            return false;
        }

        return true;
    }

    public function isMajorityMet(KingdomVote $vote): bool
    {
        $totalVotes = $vote->votesFor + $vote->votesAgainst;

        if ($totalVotes === 0) {
            return false;
        }

        $forPercent = ($vote->votesFor / $totalVotes) * 100;
        return $forPercent >= $this->config->getMajorityThresholdPercent();
    }

    public function determineVoteResult(Kingdom $kingdom, KingdomVote $vote): ?bool
    {
        if (! $this->isQuorumMet($kingdom, $vote)) {
            return false;
        }

        if (! $this->isMajorityMet($vote)) {
            return false;
        }

        return true;
    }

    private function getTotalMembers(Kingdom $kingdom): int
    {
        return count($kingdom->getOnlineMembers());
    }

    public function getQuorumErrorMessage(Kingdom $kingdom, KingdomVote $vote): string
    {
        $totalMembers = $this->getTotalMembers($kingdom);
        $totalVotes = $vote->votesFor + $vote->votesAgainst;
        $participationPercent = ($totalVotes / $totalMembers) * 100;

        if ($totalVotes < $this->config->getMinimumVotesRequired()) {
            return MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_NOT_MET, [
                ExtraTags::MINIMUM_VOTES => (string)$this->config->getMinimumVotesRequired(),
                ExtraTags::CURRENT_VOTES => (string)$totalVotes,
                ExtraTags::TOTAL_MEMBERS => (string)$totalMembers
            ]);
        }

        return MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_NOT_MET, [
            ExtraTags::QUORUM_PERCENT => number_format($this->config->getMinimumParticipationPercent(), 1),
            ExtraTags::PARTICIPATION_PERCENT => number_format($participationPercent, 1),
            ExtraTags::TOTAL_MEMBERS => (string)$totalMembers
        ]);
    }

    public function getMajorityErrorMessage(KingdomVote $vote): string
    {
        $totalVotes = $vote->votesFor + $vote->votesAgainst;
        $forPercent = ($vote->votesFor / $totalVotes) * 100;

        return MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MAJORITY_NOT_MET, [
            ExtraTags::MAJORITY_PERCENT => number_format($this->config->getMajorityThresholdPercent(), 1),
            ExtraTags::FOR_PERCENT => number_format($forPercent, 1),
            ExtraTags::FOR => (string)$vote->votesFor,
            ExtraTags::AGAINST => (string)$vote->votesAgainst
        ]);
    }

    public function getVoteStatistics(Kingdom $kingdom, KingdomVote $vote): VoteStatisticsDTO
    {
        $totalMembers = $this->getTotalMembers($kingdom);
        $totalVotes = $vote->votesFor + $vote->votesAgainst;
        $participationPercent = ($totalVotes / $totalMembers) * 100;
        $forPercent = $totalVotes > 0 ? ($vote->votesFor / $totalVotes) * 100 : 0;

        return new VoteStatisticsDTO(
            totalMembers: $totalMembers,
            totalVotes: $totalVotes,
            participationPercent: $participationPercent,
            votesFor: $vote->votesFor,
            votesAgainst: $vote->votesAgainst,
            forPercent: $forPercent,
            isQuorumMet: $this->isQuorumMet($kingdom, $vote),
            isMajorityMet: $this->isMajorityMet($vote),
            minimumParticipationPercent: $this->config->getMinimumParticipationPercent(),
            majorityRequired: $this->config->getMajorityThresholdPercent(),
            minVotesRequired: $this->config->getMinimumVotesRequired(),
            quorumPercent: $participationPercent,
        );
    }
}
