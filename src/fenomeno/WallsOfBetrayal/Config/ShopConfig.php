<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\utils\Config;

class ShopConfig
{

    public const CATEGORY_TAG  = 'ShopCategoryId';
    public const SHOP_ITEM_TAG = 'ShopItemTag';

    private static array $categoriesData = [];
    private static array $shopItemDescription = [];
    private static array $categoryItemDescription = [];

    public static function init(Main $main): void
    {
        $main->saveResource('shop.yml', true);
        $config = (new Config($main->getDataFolder() . 'shop.yml', Config::YAML))->getAll();

        self::$categoriesData = $config['categories'] ?? [];
        self::$shopItemDescription = $config['config']['shop-item-description'] ?? [];
        self::$categoryItemDescription = $config['config']['category-item-description'] ?? [];
    }

    public static function getCategoriesData(): array
    {
        return self::$categoriesData;
    }

    public static function getShopItemDescription(): array
    {
        return self::$shopItemDescription;
    }

    public static function getCategoryItemDescription(): array
    {
        return self::$categoryItemDescription;
    }

}