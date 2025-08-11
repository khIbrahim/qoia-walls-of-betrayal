<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Inventory\Actions\InventoryActions;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Throwable;

class InventoriesConfig
{

    public const CHOOSE_KINGDOM_INVENTORY  = 'choose-kingdom';
    public const CHOOSE_KIT_INVENTORY      = 'choose-kit';
    public const ABILITIES_INVENTORY       = 'abilities';
    public const SHOP_CATEGORIES_INVENTORY = 'shop-categories';
    public const SHOP_ITEMS_INVENTORY      = 'shop-items';

    /** @var array<string, InventoryDTO> */
    private static array $inventoriesDTO = [];

    private static array $data = [];

    public static function init(Main $main): void
    {
        InventoryActions::init();

        $main->saveResource('inventories.yml', true);
        self::$data = (new Config($main->getDataFolder() . 'inventories.yml', Config::YAML))->get('inventories');

        self::registerInventoriesDTO();
        $inventoriesNames = implode(", ", array_map(fn(?InventoryDTO $inventoryDTO) => $inventoryDTO?->name ?? 'Unknown Inventory', self::$inventoriesDTO));
        $main->getLogger()->info(TextFormat::AQUA . "Registered (" . count(self::$inventoriesDTO) . ") inventories DTO (" . $inventoriesNames . ")");
    }

    public static function registerInventoryDTO(string $id, array $invData): void
    {
        self::$inventoriesDTO[$id] = Utils::loadInventory($invData);
    }

    public static function registerInventoriesDTO(): void
    {
        foreach (self::$data as $id => $invData) {
            try {
                self::registerInventoryDTO($id, $invData);
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