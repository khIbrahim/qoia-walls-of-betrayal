<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Menus\EnchantingTableMenu;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\block\EnchantingTable;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class BlocksListener implements Listener
{

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block  = $event->getBlock();

        if($block instanceof EnchantingTable){
            $event->cancel();

            $session = Session::get($player);
            if(! $session->isLoaded() || $session->getKingdom() === null){
                MessagesUtils::sendTo($player, MessagesIds::NOT_IN_KINGDOM);
                return;
            }

            EnchantingTableMenu::sendTo($event->getPlayer(), $session->getKingdom());
        }
    }

}