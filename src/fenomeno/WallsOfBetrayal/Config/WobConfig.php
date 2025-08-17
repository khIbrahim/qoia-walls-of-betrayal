<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\Enum\PhaseEnum;
use fenomeno\WallsOfBetrayal\Main;

class WobConfig
{

    private const DEFAULT_REQUIREMENTS_FLUSH_INTERVAL = 5;
    private const DEFAULT_DAY_LENGTH                  = 10; // 1 heure
    private const DEFAULT_TOTAL_DAYS                  = 14;
    private const DEFAULT_VAULT_SIZE                  = 27;
    private const DEFAULT_MAX_VAULT_NUMBER            = 5;

    public const SCOREBOARD_NAME = "wob.scoreboard";

    private static int $kit_requirements_flush_interval = self::DEFAULT_REQUIREMENTS_FLUSH_INTERVAL;
    private static int $day_length                      = self::DEFAULT_DAY_LENGTH;
    private static int $totalDays                       = self::DEFAULT_TOTAL_DAYS;
    private static int $vault_size                      = self::DEFAULT_VAULT_SIZE;
    private static int $max_vault_number                = self::DEFAULT_MAX_VAULT_NUMBER;

    private static array $phaseLengths = [];

    public static function init(Main $main): void
    {
        CommandsConfig::init($main);
        InventoriesConfig::init($main);
        ShopConfig::init($main);

        $config = $main->getConfig()->getAll();

        self::$kit_requirements_flush_interval = (int) ($config['kit_requirements_flush_interval'] ?? self::DEFAULT_REQUIREMENTS_FLUSH_INTERVAL);
        self::$day_length                      = (int) ($config['day_length'] ?? self::DEFAULT_DAY_LENGTH);
        self::$totalDays                       = (int) ($config['total_days'] ?? self::DEFAULT_TOTAL_DAYS);
        self::$phaseLengths                    = self::loadPhases($config['phase_lengths'] ?? []);
        self::$vault_size                      = (int) ($config['vault_size'] ?? self::DEFAULT_VAULT_SIZE);
        self::$max_vault_number                = (int) ($config['max_vault_number'] ?? self::DEFAULT_MAX_VAULT_NUMBER);
    }

    public static function getKitRequirementsFlushInterval(): int
    {
        return self::$kit_requirements_flush_interval;
    }

    public static function getDayLength(): int
    {
        return self::$day_length;
    }

    public static function getTotalDays(): int
    {
        return self::$totalDays;
    }

    public static function getPhaseLengths(): array
    {
        return self::$phaseLengths;
    }

    public static function getVaultSize(): int
    {
        return self::$vault_size;
    }

    public static function getMaxVaultNumber(): int
    {
        return self::$max_vault_number;
    }

    private static function loadPhases(array $data): array
    {
        $phases = [];
        foreach ($data as $phaseValue => $length) {
            $phase = PhaseEnum::tryFrom($phaseValue);
            if($phase === null){
                Main::getInstance()->getLogger()->warning("§cUNKNOWN PHASE: Impossible to parse $phaseValue");
                continue;
            }

            if(! is_int($length)){
                Main::getInstance()->getLogger()->warning("§cINVALID LENGTH: Impossible to parse $phaseValue");
                continue;
            }

            $phases[$phaseValue] = $length;
        }

        return $phases;
    }

}