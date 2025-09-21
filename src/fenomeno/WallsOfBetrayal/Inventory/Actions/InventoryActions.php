<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Actions;

use fenomeno\WallsOfBetrayal\Inventory\Actions\Types\CloseInventoryAction;
use fenomeno\WallsOfBetrayal\Inventory\Actions\Types\Page\NextPageAction;
use fenomeno\WallsOfBetrayal\Inventory\Actions\Types\Page\PreviousPageAction;
use fenomeno\WallsOfBetrayal\Inventory\Loyalty\LoyaltyCausesInventory;
use fenomeno\WallsOfBetrayal\Inventory\Shop\ShopCategoryInventory;
use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use Throwable;

class InventoryActions
{

    /** @var InventoryActionInterface[] */
    private static array $handlers = [];

    private const LOYALTY_FILTER_ALL = 'loyalty_filter_all';
    private const LOYALTY_FILTER_POSITIVE = 'loyalty_filter_positive';
    private const LOYALTY_FILTER_NEGATIVE = 'loyalty_filter_negative';
    private const LOYALTY_SORT_TOGGLE = 'loyalty_sort_toggle';
    private const OPEN_SHOP_CATEGORIES = 'open_shop_categories';

    public static function init(): void
    {
        self::registerHandler(new CloseInventoryAction());
        self::registerHandler(new NextPageAction());
        self::registerHandler(new PreviousPageAction());

        // Shop
        self::registerHandler(self::makeSimpleHandler(self::OPEN_SHOP_CATEGORIES, static function(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool {
            $player->removeCurrentWindow();
            (new ShopCategoryInventory($player))->send($player);
            return true;
        }));

        // Loyalty filters & sort
        self::registerHandler(self::makeSimpleHandler(self::LOYALTY_FILTER_ALL, static function(Player $player, Item $item, int $slot, WInventory $inventory): bool {
            $player->removeCurrentWindow();
            (new LoyaltyCausesInventory(LoyaltyCausesInventory::FILTER_ALL, $inventory instanceof LoyaltyCausesInventory ? $inventory->getSortMode() : LoyaltyCausesInventory::FILTER_SORT_DESC))->send($player);
            return true;
        }));
        self::registerHandler(self::makeSimpleHandler(self::LOYALTY_FILTER_POSITIVE, static function(Player $player, Item $item, int $slot, WInventory $inventory): bool {
            $player->removeCurrentWindow();
            (new LoyaltyCausesInventory(LoyaltyCausesInventory::FILTER_POSITIVE, $inventory instanceof LoyaltyCausesInventory ? $inventory->getSortMode() : LoyaltyCausesInventory::FILTER_SORT_DESC))->send($player);
            return true;
        }));
        self::registerHandler(self::makeSimpleHandler(self::LOYALTY_FILTER_NEGATIVE, static function(Player $player, Item $item, int $slot, WInventory $inventory): bool {
            $player->removeCurrentWindow();
            (new LoyaltyCausesInventory(LoyaltyCausesInventory::FILTER_NEGATIVE, $inventory instanceof LoyaltyCausesInventory ? $inventory->getSortMode() : LoyaltyCausesInventory::FILTER_SORT_DESC))->send($player);
            return true;
        }));
        self::registerHandler(self::makeSimpleHandler(self::LOYALTY_SORT_TOGGLE, static function(Player $player, Item $item, int $slot, WInventory $inventory): bool {
            $player->removeCurrentWindow();
            $filter = $inventory instanceof LoyaltyCausesInventory ? $inventory->getFilter() : LoyaltyCausesInventory::FILTER_ALL;
            $sort   = $inventory instanceof LoyaltyCausesInventory && $inventory->getSortMode() === LoyaltyCausesInventory::FILTER_SORT_ASC ? LoyaltyCausesInventory::FILTER_SORT_DESC : LoyaltyCausesInventory::FILTER_SORT_ASC;
            (new LoyaltyCausesInventory($filter, $sort))->send($player);
            return true;
        }));
    }

    public static function handleItem(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool
    {
        foreach ($item->getNamedTag()->getValue() as $tag){
            if ($tag instanceof StringTag){
                if (isset(self::$handlers[$tag->getValue()])){
                    return self::$handlers[$tag->getValue()]->handle($player, $item, $slot, $inventory, $args);
                }
            }
        }

        return false;
    }

    private static function registerHandler(InventoryActionInterface $handler): void
    {
        self::$handlers[$handler->getId()] = $handler;
    }

    public static function handleAction(Player $player, Item $item, int $slot, string $action, WInventory $inventory, ...$args): bool
    {
        try {
            return self::$handlers[$action]->handle($player, $item, $slot, $inventory, ...$args);
        } catch (Throwable $th) {
            Main::getInstance()->getLogger()->warning("Unknown inventory handler: " . $action . " (" . $th->getMessage() . ")");
            return false;
        }
    }

    public static function simpleHandlers(): void
    {
        $test = new class implements InventoryActionInterface {

            public static function handle(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool
            {
                $player->removeCurrentWindow();

                (new ShopCategoryInventory($player))->send($player);

                return true;
            }

            public static function getId(): string
            {
                return 'open_shop_categories';
            }
        };

        self::registerHandler($test);
    }

    private static function makeSimpleHandler(string $id, callable $callback): InventoryActionInterface
    {
        return new class($id, $callback) implements InventoryActionInterface {

            private static string $id;
            private static \Closure $callback;

            public function __construct(string $id, \Closure $callback) {
                self::$id = $id;
                self::$callback = $callback;
            }

            public static function handle(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool
            {
                return (self::$callback)($player, $item, $slot, $inventory, ...$args);
            }

            public static function getId(): string
            {
                return self::$id;
            }
        };
    }

}