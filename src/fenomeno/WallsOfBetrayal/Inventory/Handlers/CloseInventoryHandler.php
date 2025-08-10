<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Handlers;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

class CloseInventoryHandler implements InventoryHandlerInterface
{

    public static function handle(Player $player, Item $item, int $slot, Inventory $inventory): bool
    {
        $player->removeCurrentWindow();

        return true;
    }

    public static function getId(): string
    {
        return "close_menu";
    }
}