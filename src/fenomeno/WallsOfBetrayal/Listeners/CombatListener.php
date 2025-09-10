<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;

class CombatListener implements Listener
{

    public function __construct(private readonly Main $main){}

    /** @noinspection PhpUnused */
    public function onDamage(EntityDamageByEntityEvent $event): void
    {
        if($event->isCancelled()){
            return;
        }

        $victim  = $event->getEntity();
        $damager = $event->getDamager();
        if(! $victim instanceof Player || ! $damager instanceof Player){
            return;
        }

        /** @var Player $player */
        foreach ([$victim, $damager] as $player) {
            if(! $this->main->getCombatManager()->canBeTagged($player)){
                continue;
            }

            $this->main->getCombatManager()->tagWithOpponent($victim, $damager);
        }
    }

    /** @noinspection PhpUnused */
    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();
        if(! $sender instanceof Player){
            return;
        }

        if(! $this->main->getCombatManager()->isTagged($sender)){
            return;
        }

        if($sender->hasPermission(PermissionIds::BYPASS_COMBAT_TAG)){
            return;
        }

        $command = strtolower($event->getCommand());
        if($this->main->getCombatManager()->isCommandBanned($command)) {
            $event->cancel();
            MessagesUtils::sendTo($sender, MessagesIds::CANT_USE_COMMAND_IN_COMBAT, [
                ExtraTags::COMMAND => $command
            ]);
        }
    }

    /** @noinspection PhpUnused */
    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        if(! $this->main->getCombatManager()->isTagged($player)){
            return;
        }

        $this->main->getCombatManager()->untag($player, true);
    }

    /** @noinspection PhpUnused */
    public function onDisconnect(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if(! $this->main->getCombatManager()->isTagged($player)){
            return;
        }

        if($this->main->getCombatManager()->canKillOnDisconnect()){
            $player->kill();
        }
    }

}