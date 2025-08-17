<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Blocks\BlockManager;
use fenomeno\WallsOfBetrayal\Blocks\Types\MobSpawnerBlock;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemBlock;
use pocketmine\player\GameMode;

class EntitiesListener implements Listener
{

    public function onPlace(BlockPlaceEvent $event) : void {
        if($event->isCancelled()){
            return;
        }
        $item = $event->getItem();
        if(!$item instanceof ItemBlock){
            return;
        }
        $block = $item->getBlock();
        if (! $block instanceof MobSpawnerBlock){
            return;
        }
        $transaction = $event->getTransaction();

        if ($item->getNamedTag()->getTag(BlockManager::ENTITY_TAG) === null){
            return;
        }
        foreach($transaction->getBlocks() as [$x, $y, $z, $blocks]){
            $transaction->addBlock($blocks->getPosition(), (new MobSpawnerBlock())->setLegacyEntityId($item->getNamedTag()->getInt(BlockManager::ENTITY_TAG)));
        }

    }

    public function onBreak(BlockBreakEvent $event) : void {
        $block = $event->getBlock();
        if($block instanceof MobSpawnerBlock){
            if ($block->getEntityInfo() !== null){
                if($event->getPlayer()->getGamemode() === GameMode::SURVIVAL){
                    $mobBlockItem = BlockManager::getInstance()->getMobSpawnerItem($block->getEntityInfo()->getLegacyId());
                    $event->setDrops([$mobBlockItem]);
                }
            }
        }
    }


}