<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\utils\Config;
use Symfony\Component\Filesystem\Path;

final class HealConfig
{
    public const FILE_NAME = 'heal_ally.yml';

    // Defaults
    private const DEFAULT_GLOBAL_COOLDOWN = 10;
    private const DEFAULT_PAIR_COOLDOWN = 60;

    private const DEFAULT_VERY_LOW_HP = 6;
    private const DEFAULT_LOW_HP = 10;
    private const DEFAULT_MEDIUM_HP = 14;

    private const DEFAULT_BASE_DELTA = 3;
    private const DEFAULT_DAILY_CAP = 200;
    private const DEFAULT_DIMINISH_K = 0.4;

    private const DEFAULT_HERO_BROADCAST_THRESHOLD = 10;
    private const DEFAULT_BADGE_DURATION_SECONDS = 600;

    private static Config $config;

    private static int $globalCooldown = self::DEFAULT_GLOBAL_COOLDOWN;
    private static int $pairCooldown = self::DEFAULT_PAIR_COOLDOWN;

    private static int $veryLowHp = self::DEFAULT_VERY_LOW_HP;
    private static int $lowHp = self::DEFAULT_LOW_HP;
    private static int $mediumHp = self::DEFAULT_MEDIUM_HP;

    private static int $baseDelta = self::DEFAULT_BASE_DELTA;
    private static int $dailyCap = self::DEFAULT_DAILY_CAP;
    private static float $diminishK = self::DEFAULT_DIMINISH_K;

    /** @var array<string, array> items config (bandage, potion_heal, ...) */
    private static array $items = [];

    private static int $heroBroadcastThreshold = self::DEFAULT_HERO_BROADCAST_THRESHOLD;
    private static int $badgeDurationSeconds = self::DEFAULT_BADGE_DURATION_SECONDS;

    public static function init(Main $main): void
    {
        $main->saveResource(self::FILE_NAME, true);

        $filePath = Path::join($main->getDataFolder(), self::FILE_NAME);
        self::$config = new Config($filePath, Config::YAML);

        $root = self::$config->getAll();

        $cd = $root['healing']['cooldowns'] ?? [];
        self::$globalCooldown = (int) ($cd['global_seconds'] ?? self::DEFAULT_GLOBAL_COOLDOWN);
        self::$pairCooldown = (int) ($cd['pair_seconds'] ?? self::DEFAULT_PAIR_COOLDOWN);

        $st = $root['healing']['severity_thresholds'] ?? [];
        self::$veryLowHp = (int) ($st['very_low_hp'] ?? self::DEFAULT_VERY_LOW_HP);
        self::$lowHp = (int) ($st['low_hp'] ?? self::DEFAULT_LOW_HP);
        self::$mediumHp = (int) ($st['medium_hp'] ?? self::DEFAULT_MEDIUM_HP);

        self::$baseDelta = (int) ($root['healing']['base_delta'] ?? self::DEFAULT_BASE_DELTA);
        $daily = $root['healing']['daily'] ?? [];
        self::$dailyCap = (int) ($daily['cap_delta'] ?? self::DEFAULT_DAILY_CAP);
        self::$diminishK = (float) ($daily['diminishing_k'] ?? self::DEFAULT_DIMINISH_K);

        $items = $root['healing']['items'] ?? [];
        $parsed = [];
        foreach ($items as $key => $icfg) {
            if (!is_array($icfg)) continue;
            $cooldown = $icfg['cooldown'] ?? 0;
            if(is_array($cooldown)){
                $cooldown['self'] = (int) ($cooldown['self'] ?? $cooldown[0] ?? 0);
                $cooldown['other'] = (int) ($cooldown['other'] ?? $cooldown[1] ?? $cooldown['self']);
            }
            $parsed[$key] = [
                'id' => $icfg['id'] ?? $key,
                'heal_hp' => isset($icfg['heal_hp']) ? (int)$icfg['heal_hp'] : null,
                'heal_percent' => isset($icfg['heal_percent']) ? (float)$icfg['heal_percent'] : null,
                'multiplier' => isset($icfg['multiplier']) ? (float)$icfg['multiplier'] : 1.0,
                'cooldown' => $cooldown,
                'min_health' => isset($icfg['min_health']) ? (int)$icfg['min_health'] : null,
            ];
        }
        self::$items = $parsed;

        $ui = $root['healing']['ui'] ?? [];
        self::$heroBroadcastThreshold = (int) ($ui['hero_broadcast_threshold'] ?? self::DEFAULT_HERO_BROADCAST_THRESHOLD);
        self::$badgeDurationSeconds = (int) ($ui['badge_duration_seconds'] ?? self::DEFAULT_BADGE_DURATION_SECONDS);
    }

    public static function getConfig(): Config
    {
        return self::$config;
    }

    public static function getGlobalCooldownSeconds(): int
    {
        return self::$globalCooldown;
    }

    public static function getPairCooldownSeconds(): int
    {
        return self::$pairCooldown;
    }

    public static function getVeryLowHpThreshold(): int
    {
        return self::$veryLowHp;
    }

    public static function getLowHpThreshold(): int
    {
        return self::$lowHp;
    }

    public static function getMediumHpThreshold(): int
    {
        return self::$mediumHp;
    }

    public static function getBaseDelta(): int
    {
        return self::$baseDelta;
    }

    public static function getDailyCapDelta(): int
    {
        return self::$dailyCap;
    }

    public static function getDiminishK(): float
    {
        return self::$diminishK;
    }

    public static function getItems(): array
    {
        return self::$items;
    }

    public static function getItemConfig(string $id): ?array
    {
        // first try direct key
        if (isset(self::$items[$id])) return self::$items[$id];

        // otherwise find by 'id' field
        foreach (self::$items as $key => $cfg) {
            if (($cfg['id'] ?? '') === $id) {
                return $cfg;
            }
        }
        return null;
    }

    public static function getHeroBroadcastThreshold(): int
    {
        return self::$heroBroadcastThreshold;
    }

    public static function getBadgeDurationSeconds(): int
    {
        return self::$badgeDurationSeconds;
    }

    public static function getBandageHeal(): int
    {
        $bandageCfg = self::getItemConfig('bandage');
        if ($bandageCfg !== null && isset($bandageCfg['heal_hp'])) {
            return (int) $bandageCfg['heal_hp'];
        }
        return 4;
    }

    public static function getBandageMinHealth(): int
    {
        $bandageCfg = self::getItemConfig('bandage');
        if ($bandageCfg !== null && isset($bandageCfg['min_health'])) {
            return (int) $bandageCfg['min_health'];
        }
        return 15;
    }

    public static function getBandageOnOtherCooldown(): int
    {
        $bandageCfg = self::getItemConfig('bandage');
        if (isset($bandageCfg['cooldown']['other']) && $bandageCfg !== null) {
            return (int) $bandageCfg['cooldown']['other'];
        }
        return 5;
    }

    public static function getBandageLoyaltyBonus(): float
    {
        $bandageCfg = self::getItemConfig('bandage');
        if ($bandageCfg !== null && isset($bandageCfg['multiplier'])) {
            return (float) ($bandageCfg['multiplier']);
        }

        return 1.0;
    }

    public static function getBandageSelfCooldown(): int
    {
        $bandageCfg = self::getItemConfig('bandage');
        if (isset($bandageCfg['cooldown']['self']) && $bandageCfg !== null) {
            return (int) $bandageCfg['cooldown']['self'];
        }
        return 0;
    }

    public static function getHealingSplashPotionHeal(): int
    {
        $potionCfg = self::getItemConfig('healing_splash_potion');
        if ($potionCfg !== null && isset($potionCfg['heal_hp'])) {
            return (int) $potionCfg['heal_hp'];
        }
        return 6;
    }

    public static function getStrongHealingSplashPotionHeal(): int
    {
        $potionCfg = self::getItemConfig('strong_healing_splash_potion');
        if ($potionCfg !== null && isset($potionCfg['heal_hp'])) {
            return (int) $potionCfg['heal_hp'];
        }
        return 8;
    }

    public static function getHealingSplashPotionMinHealth(): int
    {
        $potionCfg = self::getItemConfig('healing_splash_potion');
        if ($potionCfg !== null && isset($potionCfg['min_health'])) {
            return (int) $potionCfg['min_health'];
        }
        return 14;
    }

    public static function getStrongHealingSplashPotionMinHealth(): int
    {
        $potionCfg = self::getItemConfig('strong_healing_splash_potion');
        if ($potionCfg !== null && isset($potionCfg['min_health'])) {
            return (int) $potionCfg['min_health'];
        }

        return 12;
    }

    public static function getHealingLoyaltyBonus(): float
    {
        $potionCfg = self::getItemConfig('healing_splash_potion');
        if ($potionCfg !== null && isset($potionCfg['multiplier'])) {
            return (float) ($potionCfg['multiplier']);
        }

        return 1.25;
    }

    public static function getStrongHealingLoyaltyBonus()
    {
        $potionCfg = self::getItemConfig('strong_healing_splash_potion');
        if ($potionCfg !== null && isset($potionCfg['multiplier'])) {
            return (float) ($potionCfg['multiplier']);
        }

        return 1.5;
    }

}