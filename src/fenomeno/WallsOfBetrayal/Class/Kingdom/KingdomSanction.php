<?php

namespace fenomeno\WallsOfBetrayal\Class\Kingdom;

final class KingdomSanction
{
    /**
     * @param int $id
     * @param string $kingdomId
     * @param string $targetUuid
     * @param string $targetName
     * @param string $reason
     * @param string $staff
     * @param int $createdAt
     * @param int|null $expiresAt
     * @param bool $active
     */
    public function __construct(
        public readonly int    $id,
        public readonly string $kingdomId,
        public readonly string $targetUuid,
        public readonly string $targetName,
        public readonly string $reason,
        public readonly string $staff,
        public readonly int    $createdAt,
        public readonly ?int   $expiresAt,
        public readonly bool   $active
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            kingdomId: (string)$data['kingdom_id'],
            targetUuid: (string)$data['target_uuid'],
            targetName: (string)$data['target_name'],
            reason: (string)($data['reason'] ?? ''),
            staff: (string)($data['staff'] ?? 'System'),
            createdAt: isset($data['created_at']) && is_numeric($data['created_at'])
                ? (int)$data['created_at']
                : strtotime((string)$data['created_at'] ?? 'now'),
            expiresAt: !empty($data['expires_at']) ? (int)$data['expires_at'] : null,
            active: (bool)$data['active']
        );
    }

    public function isPermanent(): bool
    {
        return $this->expiresAt === null;
    }

    public function isTemporary(): bool
    {
        return $this->expiresAt !== null;
    }

    public function isExpired(): bool
    {
        if ($this->isPermanent()) {
            return false;
        }

        return time() >= $this->expiresAt;
    }

    public function getRemainingTime(): ?int
    {
        if ($this->isPermanent()) {
            return null;
        }

        $remaining = $this->expiresAt - time();
        return $remaining > 0 ? $remaining : 0;
    }
}
