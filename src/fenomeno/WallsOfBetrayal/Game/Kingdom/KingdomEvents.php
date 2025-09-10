<?php

namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use fenomeno\WallsOfBetrayal\Enum\PhaseEnum;
use fenomeno\WallsOfBetrayal\Events\Kingdom\PlayerEnterKingdomBaseEvent;
use fenomeno\WallsOfBetrayal\Events\Kingdom\PlayerQuitKingdomBaseEvent;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\Listener;
use pocketmine\world\sound\GhastSound;

class KingdomEvents implements Listener
{

    public function __construct(private readonly Main $main){}

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
        } else {
            if ($this->main->getPhaseManager()->getCurrentPhase()->value !== PhaseEnum::BATTLE->value){
                MessagesUtils::sendTo($player, MessagesIds::KINGDOM_BASE_CANT_ENTER_NOT_BATTLE_PHASE);
                $event->cancel();
            }
            $kingdom->broadcastMessage(MessagesIds::KINGDOM_BASE_PLAYER_ENTERED_FOREIGN, [ExtraTags::PLAYER => $player->getDisplayName()]);
            $kingdom->broadcastSound(new GhastSound());
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