<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Handlers;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

interface InventoryHandlerInterface
{

    /**
     * Doit return true si l'action a bien été éxécuté
     *
     * @param Player $player
     * @param Item $item
     * @param int $slot
     * @param Inventory $inventory
     * @return bool
     */
    public static function handle(Player $player, Item $item, int $slot, Inventory $inventory): bool;

    public static function getId(): string;

}