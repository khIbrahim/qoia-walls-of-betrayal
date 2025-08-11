<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Actions;

use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

interface InventoryActionInterface
{

    /**
     * Doit return true si l'action a bien été éxécuté
     *
     * @param Player $player
     * @param Item $item
     * @param int $slot
     * @param WInventory $inventory
     * @param mixed ...$args
     * @return bool
     */
    public static function handle(Player $player, Item $item, int $slot, WInventory $inventory, ...$args): bool;

    public static function getId(): string;

}