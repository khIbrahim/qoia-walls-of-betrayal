<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Shop;

use fenomeno\WallsOfBetrayal\Class\Shop\ShopCategory;
use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\Config\ShopConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShopItemsInventory extends WInventory
{

    public function __construct(
        private readonly ShopCategory $category
    ){parent::__construct();}

    protected function getInventoryDTO(): InventoryDTO
    {
        $dto = clone InventoriesConfig::getInventoryDTO(InventoriesConfig::SHOP_ITEMS_INVENTORY);

        $shopItems = array_values($this->category->getShopItems());
        foreach ($dto->targetIndexes as $i => $targetIndex){
            $shopItem = $shopItems[$i] ?? null;
            if(! $shopItem){
                continue;
            }

            $item = $shopItem->getItem();
            $item->setCustomName(TextFormat::RESET . $shopItem->getDisplayName());
            $item->setLore(str_replace(['{BUY_PRICE}', '{SELL_PRICE}'], [$shopItem->getBuyPrice(), $shopItem->getSellPrice()], ShopConfig::getShopItemDescription()));

            $dto->items[$targetIndex] = $item;
        }

        return $dto;
    }

    protected function onClickLegacy(Player $player, Item $item): bool
    {
        $tag = $item->getNamedTag()->getTag(ShopConfig::SHOP_ITEM_TAG);
        if ($tag === null) {
            return true;
        }

        $shopItem = Main::getInstance()->getShopManager()->getShopItemById($item->getNamedTag()->getString(ShopConfig::SHOP_ITEM_TAG, 'null'));
        if(! $shopItem){
            MessagesUtils::sendTo($player, 'shop.itemNotFound');
            return true;
        }

        $player->sendMessage("TODO");
        return true;
    }
}