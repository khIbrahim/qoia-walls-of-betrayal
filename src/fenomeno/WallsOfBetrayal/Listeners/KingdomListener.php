<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Events\PlayerJoinKingdomWorldEvent;
use fenomeno\WallsOfBetrayal\Events\PlayerLeaveKingdomWorldEvent;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\ServerManager;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;

class KingdomListener implements Listener
{

    public function __construct(private readonly Main $main){}

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

        if ($to->getFolderName() === ServerManager::KINGDOM_WORLD){
            $ev = new PlayerJoinKingdomWorldEvent($player);
        } else {
            $ev = new PlayerLeaveKingdomWorldEvent($player);
        }
        $ev->call();
    }

    public function onJoinKingdomWorld(PlayerJoinKingdomWorldEvent $event): void
    {
        var_dump($event->getPlayer()->getName() . ' joined kingdom world');
        var_dump($event->getPlayer()->getName() . ' joined kingdom world');
        var_dump($event->getPlayer()->getName() . ' joined kingdom world');
        var_dump($event->getPlayer()->getName() . ' joined kingdom world');
        var_dump($event->getPlayer()->getName() . ' joined kingdom world');
        var_dump("-------------------------------------------------------");
    }

    public function onLeaveKingdom(PlayerLeaveKingdomWorldEvent $event): void
    {
        var_dump($event->getPlayer()->getName() . ' LEAVED kingdom world');
        var_dump($event->getPlayer()->getName() . ' LEAVED kingdom world');
        var_dump($event->getPlayer()->getName() . ' LEAVED kingdom world');
        var_dump($event->getPlayer()->getName() . ' LEAVED kingdom world');
        var_dump($event->getPlayer()->getName() . ' LEAVED kingdom world');
        var_dump("-------------------------------------------------------");
    }

    public function onKill(PlayerDeathEvent $event): void
    {
        $victim        = $event->getPlayer();
        $victimSession = Session::get($victim);
        if(! $victimSession->isLoaded()){
            return;
        }
        $victimSession->addDeath();

        $victimKingdom = $victimSession->getKingdom();
        $victimKingdom?->addDeath();

        $lastDamage = $victim->getLastDamageCause();
        if ($lastDamage instanceof EntityDamageByEntityEvent) {
            $killer = $lastDamage->getDamager();
            if ($killer instanceof Player){
                $killerSession = Session::get($killer);
                if ($killerSession->isLoaded()){
                    $killerSession->addKill();

                    $killerKingdom = $killerSession->getKingdom();
                    $killerKingdom?->addKill();

                    $event->setDeathMessage("");
                    MessagesUtils::sendTo($killer->getServer(), MessagesIds::PLAYER_KILL, [
                        ExtraTags::KILLER         => $killer->getDisplayName(),
                        ExtraTags::VICTIM         => $victim->getDisplayName(),
                        ExtraTags::VICTIM_KINGDOM => $victimKingdom?->getDisplayName() ?? "null",
                        ExtraTags::KILLER_KINGDOM => $killerKingdom?->getDisplayName() ?? "null",
                    ]);
                }
            }
        }
    }

}