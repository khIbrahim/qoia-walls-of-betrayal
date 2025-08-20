<?php

namespace fenomeno\WallsOfBetrayal\Class\Punishment;

use fenomeno\WallsOfBetrayal\Utils\DurationParser;

abstract class AbstractPunishment
{

    public const TYPE_BAN    = "BAN";
    public const TYPE_MUTE   = "MUTE";
    public const TYPE_KICK   = "KICK";
    public const TYPE_JAIL   = "JAIL";
    public const TYPE_REPORT = "REPORT";

    protected int $id;
    protected string $target;
    protected string $reason;
    protected string $staff;
    protected int $createdAt;
    protected ?int $expiration;
    protected bool $active;

    public function __construct(
        string $target,
        string $reason,
        string $staff,
        ?int $expiration = null,
        int $id = 0,
        ?int $createdAt = null,
        bool $active = true
    ) {
        $this->target = $target;
        $this->reason = $reason;
        $this->staff = $staff;
        $this->expiration = $expiration;
        $this->id = $id;
        $this->createdAt = $createdAt ?? time();
        $this->active = $active;
    }

    public function isExpired(): bool
    {
        return $this->expiration !== null && $this->expiration < time();
    }

    public function isPermanent(): bool
    {
        return $this->expiration === null;
    }

    public function getDurationText(): string
    {
        return DurationParser::getReadableDuration($this->expiration);
    }

    public function getId(): int { return $this->id; }
    public function getTarget(): string { return $this->target; }
    public function getReason(): string { return $this->reason; }
    public function getStaff(): string { return $this->staff; }
    public function getCreatedAt(): int { return $this->createdAt; }
    public function getExpiration(): ?int { return $this->expiration; }
    public function isActive(): bool { return $this->active && ! $this->isExpired(); }
    public function setActive(bool $active): void { $this->active = $active; }

    public function setId(int $id): void { $this->id = $id; }

    abstract public function getType(): string;
    abstract public function toArray(): array;

    public function __toString(): string
    {
        return "Punishment Type: {$this->getType()}, Target: {$this->target}, Reason: {$this->reason}, Staff: {$this->staff}, Created At: " . date("d/m/Y H:i:s", $this->createdAt) . ", Expiration: " . ($this->expiration ? date("d/m/Y H:i:s", $this->expiration) : "Permanent") . ", Active: " . ($this->active ? "Yes" : "No");
    }

}