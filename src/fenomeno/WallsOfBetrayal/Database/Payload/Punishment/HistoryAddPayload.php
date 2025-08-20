<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Punishment;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

class HistoryAddPayload implements PayloadInterface
{

    private int $createdAt;

    public function __construct(
        public string $target,
        public string $type,
        public string $reason,
        public string $staff,
        public ?int   $expiration = null,
    ){
        $this->createdAt = time();
    }

    public function jsonSerialize(): array
    {
        return [
            "target"      => $this->target,
            "type"        => $this->type,
            "reason"      => $this->reason,
            "staff"       => $this->staff,
            "created_at"  => time(),
            "expiration"  => $this->expiration
        ];
    }
}