<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Throwable;

class InventoriesConfig
{

    public const CHOOSE_KINGDOM_INVENTORY = 'choose-kingdom';
    public const CHOOSE_KIT_INVENTORY     = 'choose-kit';

    /** @var array<string, InventoryDTO> */
    private static array $inventoriesDTO = [];

    public static function init(Main $main): void
    {
        $main->saveResource('inventories.yml', true);
        $data = (new Config($main->getDataFolder() . 'inventories.yml', Config::YAML))->getAll();

        self::registerInventoriesDTO($data['inventories'] ?? []);
        $main->getLogger()->info(TextFormat::AQUA . "Registered (" . count(self::$inventoriesDTO) . ") inventories DTO");
    }

    public static function registerInventoriesDTO(array $data): void
    {
        foreach ($data as $id => $invData) {
            try {
                self::$inventoriesDTO[$id] = Utils::loadInventory($invData);
            } catch (Throwable $e) {
                Main::getInstance()->getLogger()->error("Failed to parse inventory DTO '$id': " . $e->getMessage());
            }
        }
    }

    public static function getInventoryDTO(string $id): ?InventoryDTO
    {
        return self::$inventoriesDTO[$id] ?? null;
    }
}