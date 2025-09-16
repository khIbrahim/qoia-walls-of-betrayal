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
        switch($this) {
            case self::TRAITOR: return 0;
            case self::SUSPECT: return 20;
            case self::NEUTRAL: return 40;
            case self::LOYAL: return 70;
        }
    }

    public function getMaxScore(): int
    {
        switch($this) {
            case self::TRAITOR: return 19;
            case self::SUSPECT: return 39;
            case self::NEUTRAL: return 69;
            case self::LOYAL: return 100;
        }
    }

    public function getDisplayName(): string
    {
        switch($this) {
            case self::TRAITOR: return "§4§lTRAITOR";
            case self::SUSPECT: return "§c§lSUSPECT";
            case self::NEUTRAL: return "§7§lNEUTRAL";
            case self::LOYAL: return "§a§lLOYAL";
        }
    }

    public function getTag(): string
    {
        switch($this) {
            case self::TRAITOR: return "§4[TRAITOR]";
            case self::SUSPECT: return "§c[SUSPECT]";
            case self::NEUTRAL: return "§7[NEUTRAL]";
            case self::LOYAL: return "§a[LOYAL]";
        }
    }

    public function getColor(): string
    {
        switch($this) {
            case self::TRAITOR: return "§4";
            case self::SUSPECT: return "§c";
            case self::NEUTRAL: return "§7";
            case self::LOYAL: return "§a";
        }
    }

    public static function fromScore(int $score): self
    {
        if ($score < 20) return self::TRAITOR;
        if ($score < 40) return self::SUSPECT;
        if ($score < 70) return self::NEUTRAL;
        return self::LOYAL;
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