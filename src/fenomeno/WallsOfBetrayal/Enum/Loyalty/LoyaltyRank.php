<?php

namespace fenomeno\WallsOfBetrayal\Enum\Loyalty;

use fenomeno\WallsOfBetrayal\Main;

enum LoyaltyRank: string
{

    case TRAITOR = 'traitor';
    case SUSPECT = 'suspect';
    case NEUTRAL = 'neutral';
    case LOYAL   = 'loyal';

    public function getMinScore(): int
    {
        $ths = self::thresholds();
        return $ths[$this->value]['min'] ?? 0;
    }

    public function getMaxScore(): int
    {
        $order = [self::TRAITOR, self::SUSPECT, self::NEUTRAL, self::LOYAL];
        $ths   = self::thresholds();
        for ($i = 0; $i < count($order); $i++) {
            if ($order[$i] === $this) {
                if (isset($order[$i + 1])) {
                    $next = $order[$i + 1];
                    return ($ths[$next->value]['min'] ?? PHP_INT_MAX) - 1;
                }
                return PHP_INT_MAX;
            }
        }
        return PHP_INT_MAX;
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
        $ordered = [self::TRAITOR, self::SUSPECT, self::NEUTRAL, self::LOYAL];
        $ths = self::thresholds();

        $candidate = self::TRAITOR;
        foreach ($ordered as $rank) {
            $min = $ths[$rank->value]['min'] ?? PHP_INT_MIN;
            if ($score >= $min) {
                $candidate = $rank;
            } else {
                break;
            }
        }
        return $candidate;
    }

    public function canBetray(): bool
    {
        return $this === self::TRAITOR || $this === self::SUSPECT;
    }

    public function hasShopRestrictions(): bool
    {
        return $this === self::SUSPECT || $this === self::TRAITOR;
    }

    public function hasAbilityAccess(): bool
    {
        return $this === self::LOYAL;
    }

    public static function thresholds(): array
    {
        $cfg = Main::getInstance()->getLoyaltyManager()->getConfig();

        $defaults = [
            self::TRAITOR->value => ['min' => -500],
            self::SUSPECT->value => ['min' => 0],
            self::NEUTRAL->value => ['min' => 300],
            self::LOYAL->value   => ['min' => 3000],
        ];

        $fromCfg = $cfg->getNested('parameters.loyalty.ranks', []);
        if (! is_array($fromCfg) || $fromCfg === []) {
            return $defaults;
        }

        $ordered = [];
        foreach ([self::TRAITOR, self::SUSPECT, self::NEUTRAL, self::LOYAL] as $rank) {
            $key = $rank->value;
            $min = $defaults[$key]['min'];
            if (isset($fromCfg[$key]['min']) && is_numeric($fromCfg[$key]['min'])) {
                $min = (int) $fromCfg[$key]['min'];
            }
            $ordered[$key] = ['min' => $min];
        }

        return $ordered;
    }

}