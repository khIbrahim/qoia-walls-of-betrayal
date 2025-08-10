<?php

namespace fenomeno\WallsOfBetrayal\DTO;

use pocketmine\item\Item;

final class InventoryDTO {
    /**
     * @param string $name
     * @param int $size
     * @param string $type
     * @param Item[] $items
     * @param array<int,string> $actions  // slot => action id
     * @param int[] $targetIndexes
     * @param array<string,mixed> $meta
     */
    public function __construct(
        public string $name,
        public int    $size,
        public string $type,
        public array  $items = [],
        public array  $actions = [],
        public array  $targetIndexes = [],
        public array  $meta = []
    ){}
}