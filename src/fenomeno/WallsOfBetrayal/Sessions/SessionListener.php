<?php

namespace fenomeno\WallsOfBetrayal\Sessions;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

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

}