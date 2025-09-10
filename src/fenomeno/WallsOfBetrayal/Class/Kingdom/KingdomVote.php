<?php

namespace fenomeno\WallsOfBetrayal\Class\Kingdom;

use fenomeno\WallsOfBetrayal\Enum\KingdomVoteStatus;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use Generator;
use Stringable;

final class KingdomVote implements Stringable
{
    public function __construct(
        public int               $id,
        public string            $kingdomId,
        public KingdomVoteType   $type,
        public string            $target,
        public string            $reason,
        public string            $proposedBy,
        public int               $expiresAt,
        public int               $sanctionDuration = 0,
        public int               $votesFor = 0,
        public int               $votesAgainst = 0,
        public ?int              $createdAt = null,
        public KingdomVoteStatus $status = KingdomVoteStatus::Active
    )
    {
    }

    public static function fromArray(array $row): self
    {
        $id = (int)$row['id'];
        $kingdomId = (string)$row['kingdom_id'];
        $voteType = KingdomVoteType::from((string)$row['vote_type']);
        $target = (string)$row['target'];
        $proposedBy = (string)($row['proposed_by'] ?? 'Unknown');
        $reason = (string)($row['reason'] ?? MessagesUtils::defaultReason(''));
        $votesFor = (int)($row['votes_for'] ?? 0);
        $votesAgainst = (int)($row['votes_against'] ?? 0);
        $createdAt = $row['created_at'];
        $expiresAt = (int)$row['expires_at'];
        $sanctionDuration = (int)($row['sanction_duration'] ?? 0);
        $status = KingdomVoteStatus::tryFrom((string)$row['status']) ?? KingdomVoteStatus::Active;

        return new self(
            id: $id,
            kingdomId: $kingdomId,
            type: $voteType,
            target: $target,
            reason: $reason,
            proposedBy: $proposedBy,
            expiresAt: $expiresAt,
            sanctionDuration: $sanctionDuration,
            votesFor: $votesFor,
            votesAgainst: $votesAgainst,
            createdAt: $createdAt,
            status: $status
        );
    }

    public function isExpired(int $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function __toString(): string
    {
        return sprintf(
            "Vote #%d [%s] in kingdom '%s' targeting '%s' proposed by %s Reason: %s For: %d | Against: %d | Status: %s | Expires at: %d | Sanction Duration: %d",
            $this->id,
            $this->type->name,
            $this->kingdomId,
            $this->target,
            $this->proposedBy,
            $this->reason,
            $this->votesFor,
            $this->votesAgainst,
            $this->status->name,
            $this->expiresAt,
            $this->sanctionDuration
        );
    }

    /**
     * @return Generator<bool>|false
     */
    public function handleSuccess(Main $main): false|Generator
    {
        $handler = $main->getKingdomVoteManager()->getVoteHandler($this->type);
        if (!$handler) {
            return false;
        }

        return yield from $handler->handle($this);
    }
}
