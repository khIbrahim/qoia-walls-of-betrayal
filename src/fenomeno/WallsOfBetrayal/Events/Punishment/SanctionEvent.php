<?php

namespace fenomeno\WallsOfBetrayal\Events\Punishment;

use pocketmine\event\Event;

abstract class SanctionEvent extends Event
{
    protected string $target;
    protected string $staff;
    protected string $reason;
    protected ?int $expiration;
    protected array $extraData = [];

    public function __construct(
        string $target,
        string $staff,
        string $reason,
        ?int $expiration = null,
        array $extraData = []
    ) {
        $this->target = $target;
        $this->staff = $staff;
        $this->reason = $reason;
        $this->expiration = $expiration;
        $this->extraData = $extraData;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getStaff(): string
    {
        return $this->staff;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getExpiration(): ?int
    {
        return $this->expiration;
    }

    public function isPermanent(): bool
    {
        return $this->expiration === null;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }

    public function setExtraData(array $data): void
    {
        $this->extraData = $data;
    }

    public function addExtraData(string $key, mixed $value): void
    {
        $this->extraData[$key] = $value;
    }

    abstract public function getModerationAction(): string;

}