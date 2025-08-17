<?php

namespace fenomeno\WallsOfBetrayal\Blocks;

use fenomeno\WallsOfBetrayal\Blocks\Types\MobSpawnerBlock;
use fenomeno\WallsOfBetrayal\Entities\EntityManager;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class BlockManager
{
    use SingletonTrait;

    public const ENTITY_TAG = 'Entity';

    public function startup() : void
    {
        BlockOverrider::override('mob_spawner', new MobSpawnerBlock());

        foreach (EntityManager::getInstance()->getEntitiesInfo() as $entityInfo){
            $monsterBlock = $this->getMobSpawnerItem($entityInfo->getLegacyId());
            StringToItemParser::getInstance()->register(strtolower($entityInfo->getName()) . '_mob_spawner', fn() => $monsterBlock);
            CreativeInventory::getInstance()->add($monsterBlock);
        }
    }

    public function getMobSpawnerItem(int|string $entity, int $count = 1): ?Item
    {
        $entityInfo = EntityManager::getInstance()->getEntityInfoByName($entity);
        if ($entityInfo === null) {
            $entityInfo = EntityManager::getInstance()->getEntityInfo($entity);
            if ($entityInfo === null) {
                return null;
            }
        }

        $item = (new MobSpawnerBlock())->setLegacyEntityId($entityInfo->getLegacyId())->asItem();
        $item->getNamedTag()->setInt(self::ENTITY_TAG, $entityInfo->getLegacyId());
        $item->setCount($count);
        $item->setCustomName(TextFormat::RESET . $entityInfo->getName() . " " . TextFormat::GRAY . "Mob Spawner");
        $item->setLore([
            TextFormat::RESET . TextFormat::GRAY . "Spawn: " . TextFormat::AQUA . $entityInfo->getName(),
            TextFormat::RESET . TextFormat::GRAY . "Right click to place",
        ]);

        return $item;
    }
}