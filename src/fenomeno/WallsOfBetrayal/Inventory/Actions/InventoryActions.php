<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Actions;

use fenomeno\WallsOfBetrayal\Inventory\Actions\Types\CloseInventoryAction;
use fenomeno\WallsOfBetrayal\Inventory\Actions\Types\Page\NextPageAction;
use fenomeno\WallsOfBetrayal\Inventory\Actions\Types\Page\PreviousPageAction;
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

    public static function init(): void
    {
        self::registerHandler(new CloseInventoryAction());
        self::registerHandler(new NextPageAction());
        self::registerHandler(new PreviousPageAction());

        self::registerHandler(self::makeSimpleHandler('open_shop_categories', static function(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool {
            $player->removeCurrentWindow();
            (new ShopCategoryInventory($player))->send($player);
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