<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Shop;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\Config\ShopConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class ShopCategoryInventory extends WInventory
{

    public function __construct(protected readonly Player $player)
    {
        parent::__construct();
    }

    protected function getInventoryDTO(): InventoryDTO
    {
        $dto = clone InventoriesConfig::getInventoryDTO(InventoriesConfig::SHOP_CATEGORIES_INVENTORY);

        $shopManager = Main::getInstance()->getShopManager();
        $categories  = array_values($shopManager->getCategories());
        foreach ($dto->targetIndexes as $i => $targetIndex){
            $category = $categories[$i] ?? null;
            if(! $category){
                continue;
            }

            $dto->items[$targetIndex] = $category->getItem();
        }

        return $dto;
    }

    protected function onClickLegacy(Player $player, Item $item): bool
    {
        $tag = $item->getNamedTag()->getTag(ShopConfig::CATEGORY_TAG);
        if ($tag === null) {
            return true;
        }

        $category = Main::getInstance()->getShopManager()->getCategoryById($item->getNamedTag()->getString(ShopConfig::CATEGORY_TAG, 'null'));
        if(! $category){
            MessagesUtils::sendTo($player, 'shop.categoryNotFound');
            return true;
        }

        (new ShopItemsInventory($category))->send($player);
        return true;
    }

    protected function placeHolders(): array
    {
        return [
            '{PLAYER}' => $this->player->getName(),
        ];
    }
}
