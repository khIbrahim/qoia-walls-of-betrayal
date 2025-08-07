<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class KingdomListener implements Listener
{

    public function onJoin(PlayerJoinEvent $event): void
    {
        $event->setJoinMessage("");
    }

}