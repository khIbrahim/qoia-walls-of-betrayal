<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\Enum\PhaseEnum;
use fenomeno\WallsOfBetrayal\Main;

class WobConfig
{

    private const DEFAULT_REQUIREMENTS_FLUSH_INTERVAL = 5;
    private const DEFAULT_DAY_LENGTH                  = 10; // 1 heure
    private const DEFAULT_TOTAL_DAYS                  = 14;

    public const SCOREBOARD_NAME = "wob.scoreboard";

    private static int $kit_requirements_flush_interval = self::DEFAULT_REQUIREMENTS_FLUSH_INTERVAL;
    private static int $day_length                      = self::DEFAULT_DAY_LENGTH;
    private static int $totalDays                       = self::DEFAULT_TOTAL_DAYS;

    private static array $phaseLengths = [];

    public static function init(Main $main): void
    {
        CommandsConfig::init($main);
        InventoriesConfig::init($main);

        $config = $main->getConfig()->getAll();

        self::$kit_requirements_flush_interval = (int) ($config['kit_requirements_flush_interval'] ?? self::DEFAULT_REQUIREMENTS_FLUSH_INTERVAL);
        self::$day_length                      = (int) ($config['day_length'] ?? self::DEFAULT_DAY_LENGTH);
        self::$totalDays                       = (int) ($config['total_days'] ?? self::DEFAULT_TOTAL_DAYS);
        self::$phaseLengths                    = self::loadPhases($config['phase_lengths'] ?? []);
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