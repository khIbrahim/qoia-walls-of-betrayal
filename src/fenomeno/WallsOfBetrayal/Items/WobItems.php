<?php
namespace fenomeno\WallsOfBetrayal\Items;

use customiesdevs\customies\item\CustomiesItemFactory;
use fenomeno\WallsOfBetrayal\Items\Items\Bandage;
use fenomeno\WallsOfBetrayal\Items\Items\DivineRestorationPotion;
use pocketmine\item\Item;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static Bandage BANDAGE()
 * @method static DivineRestorationPotion DIVINE_RESTORATION_POTION()
 */
final class WobItems {
    use CloningRegistryTrait;

    protected static function register(string $name, Item $item): void
    {
        self::_registryRegister($name, $item);
    }

    /** @return Item[] */
    public static function getAll(): array {
        /** @var Item[] $result */
        $result = self::_registryGetAll();
        return $result;
    }

    protected static function setup(): void {
        self::register("bandage", CustomiesItemFactory::getInstance()->get(Bandage::IDENTIFIER));
        self::register("divine_restoration_potion", CustomiesItemFactory::getInstance()->get(DivineRestorationPotion::IDENTIFIER));
    }
}
