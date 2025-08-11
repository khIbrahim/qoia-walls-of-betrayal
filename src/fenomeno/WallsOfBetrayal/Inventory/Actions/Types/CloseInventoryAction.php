<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Actions\Types;

use fenomeno\WallsOfBetrayal\Inventory\Actions\InventoryActionInterface;
use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

class CloseInventoryAction implements InventoryActionInterface
{

    public static function handle(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool
    {
        $player->removeCurrentWindow();

        return true;
    }

    public static function getId(): string
    {
        return "close_menu";
    }
}