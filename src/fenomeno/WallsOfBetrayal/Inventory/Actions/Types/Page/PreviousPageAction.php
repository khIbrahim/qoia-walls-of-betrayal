<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Actions\Types\Page;

use fenomeno\WallsOfBetrayal\Inventory\Actions\InventoryActionInterface;
use fenomeno\WallsOfBetrayal\Inventory\Types\PageableInventory;
use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;

class PreviousPageAction implements InventoryActionInterface
{

    public static function handle(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool
    {
        $player->removeCurrentWindow();

        if(! $inventory instanceof PageableInventory){
            MessagesUtils::sendTo($player, 'common.invNotPageable');
            return true;
        }

        $inv = clone $inventory;
        $inv->previousPage();
        (new $inv(...$args))->send($player);

        return true;
    }

    public static function getId(): string
    {
        return "previous_page";
    }
}