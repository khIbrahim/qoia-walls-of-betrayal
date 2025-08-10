<?php

namespace fenomeno\WallsOfBetrayal\Class\Shop;

use fenomeno\WallsOfBetrayal\Config\ShopConfig;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;

final class ShopCategory {

    private Item $item;
    /** @var array<string,ShopItem> */
    private array $shopItems = [];

    public function __construct(
        private readonly string $id,
        private readonly string $icon,
        private readonly string $displayName
    ){
        $parsed = StringToItemParser::getInstance()->parse($this->icon) ?? VanillaItems::PAPER();
        $parsed->setCustomName($this->displayName);

        $lines = array_map(
            fn(string $line) => str_replace("{CATEGORY_NAME}", $this->displayName, $line),
            ShopConfig::getCategoryItemDescription()
        );
        if ($lines === []) { $lines = [""]; }
        $parsed->setLore($lines);

        $parsed->getNamedTag()->setString(ShopConfig::CATEGORY_TAG, $this->id);

        $this->item = $parsed;
    }

    public function getId(): string { return $this->id; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getIcon(): string { return $this->icon; }
    public function getItem(): Item { return clone $this->item; }

    /** @return array<string,ShopItem> */
    public function getShopItems(): array { return $this->shopItems; }

    /** @param array<string,ShopItem> $shopItems */
    public function setShopItems(array $shopItems): void { $this->shopItems = $shopItems; }
}