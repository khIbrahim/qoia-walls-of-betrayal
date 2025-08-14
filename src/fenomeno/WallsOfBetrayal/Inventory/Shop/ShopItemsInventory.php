<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Shop;

use fenomeno\WallsOfBetrayal\Class\Shop\ShopCategory;
use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\Config\ShopConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Inventory\Traits\InventoryPaginatorTrait;
use fenomeno\WallsOfBetrayal\Inventory\Types\PageableInventory;
use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

final class ShopItemsInventory extends WInventory implements PageableInventory
{
    use InventoryPaginatorTrait;

    public function __construct(
        private readonly ShopCategory $category,
        int $page = 0,
        ?int $batch = null
    ){
        $this->setPage($page);
        if ($batch !== null) $this->setBatch($batch);
        parent::__construct();
    }

    protected function getInventoryDTO(): InventoryDTO
    {
        $dto = clone InventoriesConfig::getInventoryDTO(InventoriesConfig::SHOP_ITEMS_INVENTORY);

        if ($this->getBatch() <= 0) {
            $this->setBatch(max(1, count($dto->targetIndexes)));
        }

        $all   = array_values($this->category->getShopItems());
        $total = count($all);

        $this->clampPage($total);

        $offset    = $this->getPage() * $this->getBatch();
        $pageItems = array_slice($all, $offset, $this->getBatch());

        foreach ($dto->targetIndexes as $i => $slot){
            $shopItem = $pageItems[$i] ?? null;
            if (!$shopItem) continue;

            $it = $shopItem->getDisplayItem();
            $it->getNamedTag()->setString(ShopConfig::SHOP_ITEM_TAG, $shopItem->getId());

            $dto->items[$slot] = $it;
        }

        return $dto;
    }

    protected function onClickLegacy(Player $player, Item $item): bool
    {
        $tag = $item->getNamedTag()->getTag(ShopConfig::SHOP_ITEM_TAG);
        if ($tag === null) return true;

        $shopItem = Main::getInstance()->getShopManager()
            ->getShopItemById($item->getNamedTag()->getString(ShopConfig::SHOP_ITEM_TAG, 'null'));

        $player->removeCurrentWindow();
        if (!$shopItem){
            MessagesUtils::sendTo($player, 'shop.itemNotFound');
            return true;
        }

        Main::getInstance()->getScheduler()->scheduleDelayedTask(
            new ClosureTask(fn() => ShopMenus::sendChooseMode($player, $shopItem)),
            5
        );
        return true;
    }

    protected function placeholders(): array
    {
        return [
            '{CATEGORY_NAME}' => $this->category->getDisplayName(),
            '{PAGE}'          => (string)($this->getPage() + 1),
            '{TOTAL_PAGES}'   => (string)$this->getTotalPages(count($this->category->getShopItems()))
        ];
    }
}