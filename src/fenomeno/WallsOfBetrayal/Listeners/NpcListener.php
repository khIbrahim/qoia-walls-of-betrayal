<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;

class NpcListener implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onWorldLoaded(WorldLoadEvent $event): void
    {
        $world = $event->getWorld();
        $npcs  = array_filter($world->getEntities(), fn(Entity $entity) => $entity instanceof NpcEntity);

        //pas la peine de les load ici ils sont load automatiquement
    }

}