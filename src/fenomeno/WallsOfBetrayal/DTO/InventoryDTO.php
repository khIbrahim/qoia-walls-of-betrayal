<?php

namespace fenomeno\WallsOfBetrayal\DTO;

use pocketmine\item\Item;

final class InventoryDTO
{

    /**
     * @param string $name
     * @param int $size
     * @param string $type
     * @param Item[] $items
     * @param array $targetIndexes
     */
    public function __construct(
        public string $name,
        public int    $size,
        public string $type,
        public array  $items = [],
        public array  $targetIndexes = [] // ici, c'est juste pour les menus qui ont des index précis, une sorte de metadata
    ){}

}