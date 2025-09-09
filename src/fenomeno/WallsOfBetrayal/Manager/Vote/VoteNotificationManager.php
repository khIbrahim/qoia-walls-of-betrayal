<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Manager\Vote;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Config\KingdomVotingConfig;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteStatus;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\scheduler\ClosureTask;

final class VoteNotificationManager
{
    private KingdomVotingConfig $config;
    private Main $main;
    
    private array $reminderCounts = [];
    
    public function __construct(Main $main)
    {
        $this->config = new KingdomVotingConfig($main);
        $this->main = $main;
    }

    public function notifyVoteCreated(Kingdom $kingdom, KingdomVote $vote): void
    {
        if (! $this->config->isNotificationOnVoteCreatedEnabled()) {
            return;
        }
        
        $message = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_CREATED_NOTIFICATION, [
            ExtraTags::TYPE        => strtoupper($vote->type->value),
            ExtraTags::TARGET      => $vote->target,
            ExtraTags::REASON      => $vote->reason,
            ExtraTags::PROPOSED_BY => $vote->proposedBy,
            ExtraTags::EXPIRES_AT  => date('Y-m-d H:i', $vote->expiresAt)
        ]);
        
        $kingdom->broadcastMessage($message);
        
        if ($this->config->isReminderEnabled()) {
            $this->scheduleReminder($vote->id, $kingdom, $vote);
        }
    }
    
    public function notifyMemberVoted(Kingdom $kingdom, KingdomVote $vote, string $voterName, bool $voteFor): void
    {
        if (!$this->config->isNotificationOnMemberVotedEnabled()) {
            return;
        }
        
        $message = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MEMBER_VOTED_NOTIFICATION, [
            ExtraTags::PLAYER      => $voterName,
            ExtraTags::VOTE_CHOICE => $voteFor ? 'FOR' : 'AGAINST',
            ExtraTags::TYPE        => strtoupper($vote->type->value),
            ExtraTags::TARGET      => $vote->target,
            ExtraTags::FOR         => $vote->target,
            ExtraTags::AGAINST     => $vote->target
        ]);
        
        $kingdom->broadcastMessage($message);
    }
    
    public function notifyVoteExpired(Kingdom $kingdom, KingdomVote $vote): void
    {
        if (! $this->config->isNotificationOnVoteExpiredEnabled()) {
            return;
        }
        
        $message = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_EXPIRED_NOTIFICATION, [
            ExtraTags::TYPE => strtoupper($vote->type->value),
            ExtraTags::TARGET => $vote->target,
            ExtraTags::FOR => (string)$vote->votesFor,
            ExtraTags::AGAINST => (string)$vote->votesAgainst
        ]);
        
        $kingdom->broadcastMessage($message);
    }
    
    public function notifyVoteResult(Kingdom $kingdom, KingdomVote $vote, bool $passed): void
    {
        if (!$this->config->isNotificationOnVoteResultEnabled()) {
            return;
        }
        
        $messageId = $passed ? MessagesIds::KINGDOMS_VOTE_PASSED_NOTIFICATION : MessagesIds::KINGDOMS_VOTE_FAILED_NOTIFICATION;
        
        $message = MessagesUtils::getMessage($messageId, [
            ExtraTags::TYPE    => strtoupper($vote->type->value),
            ExtraTags::TARGET  => $vote->target,
            ExtraTags::FOR     => (string)$vote->votesFor,
            ExtraTags::AGAINST => (string)$vote->votesAgainst,
            ExtraTags::TOTAL   => (string)($vote->votesFor + $vote->votesAgainst)
        ]);
        
        $kingdom->broadcastMessage($message);
    }
    
    private function scheduleReminder(int $voteId, Kingdom $kingdom, KingdomVote $vote): void
    {
        $reminderCount = $this->reminderCounts[$voteId] ?? 0;
        
        if ($reminderCount >= $this->config->getReminderMaxCount()) {
            return;
        }
        
        $delay = $this->config->getReminderIntervalMinutes() * 60 * 20; // Conversion en ticks
        
        $this->main->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function() use ($voteId, $kingdom, $vote, $reminderCount): void {
                if ($vote->status !== KingdomVoteStatus::Active) {
                    return;
                }
                
                $this->sendReminder($kingdom, $vote, $reminderCount + 1);
                $this->reminderCounts[$voteId] = $reminderCount + 1;
                
                $this->scheduleReminder($voteId, $kingdom, $vote);
            }), 
            $delay
        );
    }

    private function sendReminder(Kingdom $kingdom, KingdomVote $vote, int $reminderNumber): void
    {
        $message = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_REMINDER_NOTIFICATION, [
            ExtraTags::TYPE            => strtoupper($vote->type->value),
            ExtraTags::TARGET          => $vote->target,
            ExtraTags::REMAINING_TIME  => DurationParser::getReadableDuration($vote->expiresAt),
            ExtraTags::REMINDER_NUMBER => (string)$reminderNumber
        ]);
        
        $kingdom->broadcastMessage($message);
    }

    public function cleanupReminders(int $voteId): void
    {
        unset($this->reminderCounts[$voteId]);
    }
}
