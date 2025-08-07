<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Main;

class KingdomConfig
{

    public const SCOREBOARD_NAME = "wob.scoreboard";

    private static ?InventoryDTO $chooseInventoryDTO = null;

    public static function init(Main $main): void
    {
        $config = $main->getConfig()->getAll();
        CommandsConfig::init($main);

        self::$chooseInventoryDTO = Utils::loadInventory($config['inventories']['choose-kingdom'] ?? []);
    }

    public static function getChooseInventoryDTO(): ?InventoryDTO
    {
        return self::$chooseInventoryDTO;
    }

}