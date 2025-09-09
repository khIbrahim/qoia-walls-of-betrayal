<?php
declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\Main;

final class KingdomVotingConfig
{
    private array $config;

    private const DEFAULT_MINIMUM_PARTICIPATION_PERCENT = 50.0;
    private const DEFAULT_MINIMUM_VOTES_REQUIRED = 3;
    private const DEFAULT_MAJORITY_THRESHOLD_PERCENT = 60.0;

    private const DEFAULT_NOTIFICATION_ON_VOTE_CREATED = true;
    private const DEFAULT_NOTIFICATION_ON_MEMBER_VOTED = true;
    private const DEFAULT_NOTIFICATION_ON_VOTE_EXPIRED = true;
    private const DEFAULT_NOTIFICATION_ON_VOTE_RESULT = true;

    private const DEFAULT_REMINDER_ENABLED = true;
    private const DEFAULT_REMINDER_INTERVAL_MINUTES = 30;
    private const DEFAULT_REMINDER_MAX_COUNT = 3;

    private const DEFAULT_VOTE_DURATION_SECONDS = 259200;

    public function __construct(Main $main)
    {
        $this->config = $main->getConfig()->getAll();
    }

    public function getVoteDurationSeconds(): int
    {
        return (int) ($this->config['voting']['vote_duration_seconds'] ?? self::DEFAULT_VOTE_DURATION_SECONDS);
    }

    public function getMinimumParticipationPercent(): float
    {
        return (float) ($this->config['voting']['quorum']['minimum_participation_percent'] ?? self::DEFAULT_MINIMUM_PARTICIPATION_PERCENT);
    }

    public function getMinimumVotesRequired(): int
    {
        return (int) ($this->config['voting']['quorum']['minimum_votes_required'] ?? self::DEFAULT_MINIMUM_VOTES_REQUIRED);
    }

    public function getMajorityThresholdPercent(): float
    {
        return (float) ($this->config['voting']['quorum']['majority_threshold_percent'] ?? self::DEFAULT_MAJORITY_THRESHOLD_PERCENT);
    }

    public function isNotificationOnVoteCreatedEnabled(): bool
    {
        return (bool) ($this->config['voting']['notifications']['on_vote_created'] ?? self::DEFAULT_NOTIFICATION_ON_VOTE_CREATED);
    }

    public function isNotificationOnMemberVotedEnabled(): bool
    {
        return (bool) ($this->config['voting']['notifications']['on_member_voted'] ?? self::DEFAULT_NOTIFICATION_ON_MEMBER_VOTED);
    }

    public function isNotificationOnVoteExpiredEnabled(): bool
    {
        return (bool) ($this->config['voting']['notifications']['on_vote_expired'] ?? self::DEFAULT_NOTIFICATION_ON_VOTE_EXPIRED);
    }

    public function isNotificationOnVoteResultEnabled(): bool
    {
        return (bool) ($this->config['voting']['notifications']['on_vote_result'] ?? self::DEFAULT_NOTIFICATION_ON_VOTE_RESULT);
    }

    public function isReminderEnabled(): bool
    {
        return (bool) ($this->config['voting']['notifications']['reminder_enabled'] ?? self::DEFAULT_REMINDER_ENABLED);
    }

    public function getReminderIntervalMinutes(): int
    {
        return (int) ($this->config['voting']['notifications']['reminder_interval_minutes'] ?? self::DEFAULT_REMINDER_INTERVAL_MINUTES);
    }

    public function getReminderMaxCount(): int
    {
        return (int) ($this->config['voting']['notifications']['reminder_max_count'] ?? self::DEFAULT_REMINDER_MAX_COUNT);
    }
}