<?php

namespace fenomeno\WallsOfBetrayal\Utils;

class CooldownManager
{
    /** @var array<string, array<string, int>> */
    private static array $cooldowns = [];

    public static function isOnCooldown(string $type, string $identifier): bool
    {
        if (! isset(self::$cooldowns[$type][$identifier])) {
            return false;
        }

        return self::$cooldowns[$type][$identifier] > time();
    }

    public static function getCooldownRemaining(string $type, string $identifier): int
    {
        if (! self::isOnCooldown($type, $identifier)) {
            unset(self::$cooldowns[$type][$identifier]);
            return 0;
        }

        return self::$cooldowns[$type][$identifier] - time();
    }

    public static function setCooldown(string $type, string $identifier, int $duration): void
    {
        if (! isset(self::$cooldowns[$type])) {
            self::$cooldowns[$type] = [];
        }
        self::$cooldowns[$type][$identifier] = time() + $duration;
    }

}