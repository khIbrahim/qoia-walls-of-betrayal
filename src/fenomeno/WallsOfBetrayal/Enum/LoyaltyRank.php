<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum LoyaltyRank: string
{
    case TRAITOR = 'traitor';
    case SUSPECT = 'suspect';
    case NEUTRAL = 'neutral';
    case LOYAL = 'loyal';

    public function getMinScore(): int
    {
        return match($this) {
            self::TRAITOR => 0,
            self::SUSPECT => 20,
            self::NEUTRAL => 40,
            self::LOYAL => 70,
        };
    }

    public function getMaxScore(): int
    {
        return match($this) {
            self::TRAITOR => 19,
            self::SUSPECT => 39,
            self::NEUTRAL => 69,
            self::LOYAL => 100,
        };
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::TRAITOR => "§4§lTRAITOR",
            self::SUSPECT => "§c§lSUSPECT",
            self::NEUTRAL => "§7§lNEUTRAL",
            self::LOYAL => "§a§lLOYAL",
        };
    }

    public function getTag(): string
    {
        return match($this) {
            self::TRAITOR => "§4[TRAITOR]",
            self::SUSPECT => "§c[SUSPECT]",
            self::NEUTRAL => "§7[NEUTRAL]",
            self::LOYAL => "§a[LOYAL]",
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::TRAITOR => "§4",
            self::SUSPECT => "§c",
            self::NEUTRAL => "§7",
            self::LOYAL => "§a",
        };
    }

    public static function fromScore(int $score): self
    {
        return match(true) {
            $score < 20 => self::TRAITOR,
            $score < 40 => self::SUSPECT,
            $score < 70 => self::NEUTRAL,
            default => self::LOYAL,
        };
    }

    public function canBetray(): bool
    {
        return $this === self::TRAITOR;
    }

    public function hasShopRestrictions(): bool
    {
        return $this === self::SUSPECT || $this === self::TRAITOR;
    }

    public function hasAbilityAccess(): bool
    {
        return $this === self::LOYAL;
    }
}