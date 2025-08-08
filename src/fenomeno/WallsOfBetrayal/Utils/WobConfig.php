<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\Main;

class WobConfig
{

    public const SCOREBOARD_NAME = "wob.scoreboard";

    private static int $kit_requirements_flush_interval = 5;

    public static function init(Main $main): void
    {
        CommandsConfig::init($main);
        InventoriesConfig::init($main);

        $config = $main->getConfig()->getAll();

        self::$kit_requirements_flush_interval = (int) ($config['kit_requirements_flush_interval'] ?? 5);
    }

    public static function getKitRequirementsFlushInterval(): int
    {
        return self::$kit_requirements_flush_interval;
    }

}