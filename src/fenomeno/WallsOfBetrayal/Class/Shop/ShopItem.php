<?php

namespace fenomeno\WallsOfBetrayal\Class\Shop;

use pocketmine\item\Item;

final class ShopItem {

    public function __construct(
        private readonly string $id,
        private readonly Item $item,
        private readonly string $displayName,
        private readonly int $buyPrice,
        private readonly int $sellPrice,
        private readonly string $categoryId
    ){}

    public function getId(): string { return $this->id; }
    public function getItem(): Item { return clone $this->item; } // sécurité
    public function getDisplayName(): string { return $this->displayName; }
    public function getBuyPrice(): int { return $this->buyPrice; }
    public function getSellPrice(): int { return $this->sellPrice; }
    public function getCategoryId(): string { return $this->categoryId; }
}