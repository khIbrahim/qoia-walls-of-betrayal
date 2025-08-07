<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\Main;

class WobConfig
{

    public const SCOREBOARD_NAME = "wob.scoreboard";

    public static function init(Main $main): void
    {
        CommandsConfig::init($main);
        InventoriesConfig::init($main);
    }

}