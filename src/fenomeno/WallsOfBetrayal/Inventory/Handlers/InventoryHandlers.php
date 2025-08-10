<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Handlers;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use Throwable;

class InventoryHandlers
{

    /** @var InventoryHandlerInterface[] */
    private static array $handlers = [];

    public static function init(): void
    {
        self::registerHandler(new CloseInventoryHandler());
    }

    public static function handleItem(Player $player, Item $item, int $slot, Inventory $inventory): bool
    {
        foreach ($item->getNamedTag()->getValue() as $tag){
            if ($tag instanceof StringTag){
                if (isset(self::$handlers[$tag->getValue()])){
                    return self::$handlers[$tag->getValue()]->handle($player, $item, $slot, $inventory);
                }
            }
        }

        return false;
    }

    private static function registerHandler(InventoryHandlerInterface $handler): void
    {
        self::$handlers[$handler->getId()] = $handler;
    }

    public static function handleAction(Player $player, Item $item, int $slot, string $action, Inventory $inventory): bool
    {
        try {
            return self::$handlers[$action]->handle($player, $item, $slot, $inventory);
        } catch (Throwable $th) {
            Main::getInstance()->getLogger()->warning("Unknown inventory handler: " . $action . " (" . $th->getMessage() . ")");
            return false;
        }
    }

}