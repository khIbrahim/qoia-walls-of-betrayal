<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\KillAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
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

        $this->main->getAbilityManager()->triggerAbilityType($killer, KillAbilityInterface::class, 'onKill', $victim);
    }

}