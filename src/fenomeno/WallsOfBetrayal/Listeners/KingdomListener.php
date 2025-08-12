<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Entities\PortalEntity;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\WobChatFormatter;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;

class KingdomListener implements Listener
{

    public function onJoin(PlayerJoinEvent $event): void
    {
        $event->setJoinMessage("");
    }

    public function onMove(PlayerMoveEvent $event): void
    {
        if(! Session::get($event->getPlayer())->isLoaded()){
            $event->cancel();
        }
    }

    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();
        if($sender instanceof Player){
            if(! Session::get($sender)->isLoaded()){
                $event->cancel();
            }
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        if(! Session::get($event->getPlayer())->isLoaded()){
            $event->cancel();
        }
        $event->setFormatter(new WobChatFormatter(Session::get($event->getPlayer())));

//        $message = $event->getMessage();
//        if (str_starts_with($message, 'portal')){
//            $parts = explode(" ", $message);
//            if(count($parts) === 2){
//                $kingdomId = $parts[1];
//                $kingdom = Main::getInstance()->getKingdomManager()->getKingdomById($kingdomId);
//                if ($kingdom){
//                    $player = $event->getPlayer();
//                    $entity = new PortalEntity($player->getLocation(), $kingdom->portalId);
//                    $entity->spawnToAll();
//                    $player->sendMessage("§aPORTAIL SPAWN");
//                }
//            }
//        }
    }

}