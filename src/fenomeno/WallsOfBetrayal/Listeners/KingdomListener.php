<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Entities\PortalEntity;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;

class KingdomListener implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onJoin(PlayerJoinEvent $event): void
    {
        $event->setJoinMessage("");
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player  = $event->getPlayer();
        $message = $event->getMessage();
        if (str_starts_with($message, 'portal')){
            $parts = explode(" ", $message);
            if(count($parts) === 2){
                $kingdomId = $parts[1];
                $kingdom = $this->main->getKingdomManager()->getKingdomById($kingdomId);
                if ($kingdom){
                    $entity = new PortalEntity($player->getLocation(), $kingdom->portalId);
                    $entity->spawnToAll();
                    $player->sendMessage("§aPORTAIL SPAWN");
                }
            }
        }
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