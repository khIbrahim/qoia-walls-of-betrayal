<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Blocks\BlockManager;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Entities\EntityInfo;
use fenomeno\WallsOfBetrayal\Entities\EntityManager;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\item\Item;
use pocketmine\player\Player;

class SpawnersInventory extends WInventory
{

    protected function getInventoryDTO(): InventoryDTO
    {
        return new InventoryDTO(
            "Spawners",
            27,
            Utils::getInvMenuSize(27),
            array_map(function (EntityInfo $entityInfo) {
                return BlockManager::getInstance()->getMobSpawnerItem($entityInfo->getLegacyId());
            }, array_values(EntityManager::getInstance()->getEntitiesInfo())),
        );
    }

    protected function onClickLegacy(Player $player, Item $item): bool
    {
        if ($item->getNamedTag()->getTag(BlockManager::ENTITY_TAG) !== null){
            $player->removeCurrentWindow();
            $mobItem = BlockManager::getInstance()->getMobSpawnerItem($item->getNamedTag()->getInt(BlockManager::ENTITY_TAG));
            if($mobItem === null){
                MessagesUtils::sendTo($player, MessagesIds::SPAWNER_NOT_FOUND, [
                    ExtraTags::PLAYER => $player->getName(),
                    ExtraTags::SPAWNER => $item->getCustomName()
                ]);
                return false;
            }
            if ($player->getInventory()->canAddItem($mobItem)){
                $player->getInventory()->addItem($mobItem);
            } else {
                $player->getWorld()->dropItem($player->getPosition(), $mobItem);
            }
            MessagesUtils::sendTo($player, MessagesIds::SPAWNER_ADDED, [
                ExtraTags::PLAYER  => $player->getName(),
                ExtraTags::SPAWNER => $mobItem->getCustomName(),
                ExtraTags::NUMBER  => 1
            ]);

            return true;
        }

        return false;
    }

}