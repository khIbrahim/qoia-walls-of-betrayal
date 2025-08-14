<?php

namespace fenomeno\WallsOfBetrayal\Sessions;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;

class SessionListener implements Listener
{

    /**
     * @priority MONITOR
     *
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        Session::get($event->getPlayer())->load();
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerChatEvent $event
     * @return void
     */
    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        if (!Session::get($player)->isLoaded()) {
            $event->cancel();
            return;
        }
    }

    /**
     * @priority MONITOR
     *
     * @param CommandEvent $event
     * @return void
     */
    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();
        if($sender instanceof Player){
            if(! Session::get($sender)->isLoaded()){
                $event->cancel();
            }
        }
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerMoveEvent $event
     * @return void
     */
    public function onMove(PlayerMoveEvent $event): void
    {
        if(! Session::get($event->getPlayer())->isLoaded()){
            $event->cancel();
        }
    }

}