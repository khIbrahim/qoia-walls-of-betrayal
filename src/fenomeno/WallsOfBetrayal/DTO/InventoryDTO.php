<?php

namespace fenomeno\WallsOfBetrayal\DTO;

final class InventoryDTO
{

    public function __construct(
        public string $name,
        public int    $size,
        public string $type,
        public array  $items = [],
        public array  $targetIndexes = [] // ici, c'est juste pour les menus qui ont des index précis, une sorte de metadata
    ){}

}