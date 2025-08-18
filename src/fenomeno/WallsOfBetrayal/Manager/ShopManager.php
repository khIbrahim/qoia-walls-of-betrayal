<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Shop\ShopCategory;
use fenomeno\WallsOfBetrayal\Class\Shop\ShopItem;
use fenomeno\WallsOfBetrayal\Config\ShopConfig;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use Throwable;

final class ShopManager {

    /** @var array<string,ShopCategory> */
    private array $categories = [];
    /** @var array<string,ShopItem> */
    private array $shopItems = [];

    public function __construct(private readonly Main $main) {
        $this->load(ShopConfig::getCategoriesData());

        $categoriesNames = implode(", ", array_map(fn(ShopCategory $c) => $c->getDisplayName(), $this->categories));
        $shopItemsNames  = implode(", ", array_map(fn(ShopItem $s) => $s->getDisplayName(), $this->shopItems));
        $this->main->getLogger()->info("§aSHOP - Loaded " . count($this->categories) . " categories ($categoriesNames)");
        $this->main->getLogger()->info("§aSHOP - Loaded " . count($this->shopItems) . " items ($shopItemsNames)");
    }

    private function load(array $data): void {
        $categories = [];
        $parser = StringToItemParser::getInstance();

        foreach ($data as $categoryId => $categoryData) {
            try {
                if (!isset($categoryData['icon'], $categoryData['display-name'], $categoryData['items']) || !is_array($categoryData['items'])) {
                    $this->main->getLogger()->error("SHOP - Category '$categoryId' invalid: require (icon, display-name, items[])");
                    continue;
                }

                $displayName = (string)$categoryData['display-name'];

                $category = new ShopCategory(
                    id: $categoryId,
                    icon: (string)$categoryData['icon'],
                    displayName: $displayName
                );

                $shopItems = [];

                foreach ($categoryData['items'] as $i => $itemData) {
                    try {
                        if (!isset($itemData['item'], $itemData['buy'], $itemData['sell'])) {
                            $this->main->getLogger()->error("SHOP - Item #$i in '$categoryId' invalid: require (item, buy, sell)");
                            continue;
                        }

                        $item = $parser->parse($itemData['item']);
                        if (! $item) {
                            $this->main->getLogger()->error("SHOP - Item #$i in '$categoryId': unknown item '{$itemData['item']}'");
                            continue;
                        }

                        $count = (int)($itemData['count'] ?? 1);
                        if ($count < 1 || $count > 64) {
                            $this->main->getLogger()->warning("SHOP - Item #$i in '$categoryId': count=$count out of [1..64], clamped.");
                            $count = max(1, min(64, $count));
                        }

                        $item->setCount($count);

                        $buy  = max(0, (int)$itemData['buy']);
                        $sell = max(0, (int)$itemData['sell']);
                        if ($sell > $buy && $buy > 0) {
                            $this->main->getLogger()->warning("SHOP - Item #$i in '$categoryId': sell($sell) > buy($buy), clamped to buy.");
                            $sell = $buy;
                        }

                        $uniqueId = $categoryId . ':' . $i;
                        $displayItemName = (string)($itemData['display-name'] ?? $item->getName());

                        $shopItem = new ShopItem(
                            id: $uniqueId,
                            item: $item,
                            displayName: $displayItemName,
                            buyPrice: $buy,
                            sellPrice: $sell,
                            categoryId: $categoryId
                        );

                        $shopItems[$uniqueId] = $shopItem;
                        $this->shopItems[$uniqueId] = $shopItem;

                    } catch (Throwable $e) {
                        $this->main->getLogger()->error("SHOP - Item #$i in '$categoryId' failed: " . $e->getMessage());
                    }
                }

                $category->setShopItems($shopItems);
                $categories[$categoryId] = $category;

            } catch (Throwable $e) {
                $this->main->getLogger()->error("SHOP - Category '$categoryId' failed: " . $e->getMessage());
            }
        }

        $this->categories = $categories;
    }

    /** @return array<string,ShopCategory> */
    public function getCategories(): array { return $this->categories; }

    public function getItem(string $id): ?ShopItem {
        return $this->shopItems[$id] ?? null;
    }

    public function getCategoryById(string $categoryId): ?ShopCategory
    {
        return $this->categories[$categoryId] ?? null;
    }

    public function getShopItemById(string $shopItemId): ?ShopItem
    {
        return $this->shopItems[$shopItemId] ?? null;
    }

    public function getShopItemByItem(Item $item): ?ShopItem
    {
        foreach ($this->shopItems as $shopItem) {
            if ($shopItem->getItem()->getTypeId() === $item->getTypeId()){
                return $shopItem;
            }
        }

        return null;
    }
}