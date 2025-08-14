<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Entities\PortalEntity;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
class KingdomListener implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onJoin(PlayerJoinEvent $event): void
    {
        $event->setJoinMessage("");
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player  = $event->getPlayer();
        $message = $event->getMessage();
        if (str_starts_with($message, 'portal')){
            $parts = explode(" ", $message);
            if(count($parts) === 2){
                $kingdomId = $parts[1];
                $kingdom = $this->main->getKingdomManager()->getKingdomById($kingdomId);
                if ($kingdom){
                    $entity = new PortalEntity($player->getLocation(), $kingdom->portalId);
                    $entity->spawnToAll();
                    $player->sendMessage("§aPORTAIL SPAWN");
                }
            }
        }
    }

}