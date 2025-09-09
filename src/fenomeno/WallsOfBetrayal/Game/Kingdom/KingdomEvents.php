<?php

namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use fenomeno\WallsOfBetrayal\Events\Kingdom\PlayerEnterKingdomBaseEvent;
use fenomeno\WallsOfBetrayal\Events\Kingdom\PlayerQuitKingdomBaseEvent;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use pocketmine\event\Listener;

class KingdomEvents implements Listener
{

    public function onJoinBase(PlayerEnterKingdomBaseEvent $event): void
    {
        $player  = $event->getPlayer();
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return;
        }

        $kingdom = $event->getKingdom();
        if($session->getKingdom()?->getId() === $kingdom->getId()){
            $kingdom->broadcastMessage(MessagesIds::KINGDOM_BASE_PLAYER_ENTERED_OWN, [ExtraTags::PLAYER => $player->getDisplayName()]);
        }
    }

    public function onQuitBase(PlayerQuitKingdomBaseEvent $event): void
    {
        $player  = $event->getPlayer();
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return;
        }

        $kingdom = $event->getKingdom();
        if($session->getKingdom()?->getId() === $kingdom->getId()){
            $kingdom->broadcastMessage(MessagesIds::KINGDOM_BASE_PLAYER_QUIT_OWN, [ExtraTags::PLAYER => $player->getDisplayName()]);
        }
    }

}