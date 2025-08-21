<?php

namespace fenomeno\WallsOfBetrayal\DTO;

use fenomeno\WallsOfBetrayal\Utils\DurationParser;

final readonly class PunishmentHistoryEntry
{
    public function __construct(
        public string $target,
        public string $type,
        public string $reason,
        public string $staff,
        public int    $createdAt,
        public ?int   $expiration
    ) {}

    public function getDurationText(): string
    {
        if ($this->expiration === null || $this->expiration >= PHP_INT_MAX) {
            return "PERMANENT";
        }
        return DurationParser::getReadableDuration($this->expiration);
    }

    public function getCreatedAtFormatted(): string
    {
        return date("d/m/Y H:i:s", $this->createdAt);
    }
}