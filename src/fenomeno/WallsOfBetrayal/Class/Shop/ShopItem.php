<?php

namespace fenomeno\WallsOfBetrayal\Class\Shop;

use fenomeno\WallsOfBetrayal\Config\ShopConfig;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

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

    public function getDisplayItem(): Item
    {
        $item = $this->getItem();
        $item->setCustomName(TextFormat::RESET . $this->getDisplayName());
        $item->setLore(str_replace(['{BUY_PRICE}', '{SELL_PRICE}'], [$this->getBuyPrice(), $this->getSellPrice()], ShopConfig::getShopItemDescription()));
        $item->getNamedTag()->setString(ShopConfig::SHOP_ITEM_TAG, $this->getId());

        return $item;
    }
}