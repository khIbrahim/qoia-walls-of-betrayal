<?php

namespace fenomeno\WallsOfBetrayal\Class\Punishment;

class Ban extends AbstractPunishment
{
    private bool $silent;

    public function __construct(
        string $target,
        string $reason,
        string $staff,
        ?int $expiration = null,
        bool $silent = false,
        int $id = 0,
        ?int $createdAt = null,
        bool $active = true
    ) {
        parent::__construct($target, $reason, $staff, $expiration, $id, $createdAt, $active);
        $this->silent = $silent;
    }

    public function getType(): string
    {
        return self::TYPE_BAN;
    }

    public function isSilent(): bool { return $this->silent; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'target' => $this->target,
            'reason' => $this->reason,
            'staff' => $this->staff,
            'created_at' => $this->createdAt,
            'expiration' => $this->expiration ?? null,
            'silent' => $this->silent ? 1 : 0,
            'active' => $this->active
        ];
    }
}