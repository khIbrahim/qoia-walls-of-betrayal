<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Game\Abilities\Types\KillAbilityInterface;
use fenomeno\WallsOfBetrayal\Game\Handlers\AbilityUseHandler;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\player\Player;

class AbilitiesListener implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onKill(PlayerDeathEvent $event): void {
        $victim = $event->getPlayer();
        $cause  = $victim->getLastDamageCause();

        if (! $cause instanceof EntityDamageByEntityEvent) {
            return;
        }
        $killer = $cause->getDamager();
        if (! $killer instanceof Player) {
            return;
        }

        $session = Session::get($killer);
        if(! $session->isLoaded()){
            return;
        }

        $this->main->getAbilityManager()->triggerAbilityType($killer, KillAbilityInterface::class, $victim);
    }

    public function onUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        if (! Session::get($player)->isLoaded()) return;

        if (AbilityUseHandler::useItem($player, $event->getItem())){
            $event->cancel();
        }
    }

}