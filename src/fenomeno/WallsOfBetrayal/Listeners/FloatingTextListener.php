<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Exceptions\FloatingText\UnknownFloatingTextException;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class FloatingTextListener implements Listener
{

    public function __construct(
        private readonly Main $main
    ){}

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        foreach ($this->main->getFloatingTextManager()->getAll() as $id => $_) {
            try {$this->main->getFloatingTextManager()->sendFloatingText($player, $id);} catch (UnknownFloatingTextException){}
        }
    }

    public function onWorldChange(EntityTeleportEvent $event): void {
        $player = $event->getEntity();
        if(! $player instanceof Player){
            return;
        }

        $from = $event->getFrom()->getWorld();
        $to   = $event->getTo()->getWorld();

        if($from->getFolderName() === $to->getFolderName()){
            return;
        }

        $floatingManager = $this->main->getFloatingTextManager();
        $this->main->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $to, $from, $floatingManager): void {
            foreach ($floatingManager->getAll() as $id => $data) {
                $textWorld = $data->getPosition()->getWorld()->getFolderName();
                if ($to->getFolderName() === $textWorld) {
                    $floatingManager->sendFloatingText($player, $id);
                } else {
                    $floatingManager->cleanupFloatingText($id, $player);
                }
            }
        }), 1);
    }


}