<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyCause;
use fenomeno\WallsOfBetrayal\Events\Combat\PlayerCombatLogoutEvent;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;

final class LoyaltyListener implements Listener
{

    public function __construct(private readonly Main $main){}

    /** @noinspection PhpUnused */
    public function onDeath(PlayerDeathEvent $event): void
    {
        $this->main->getLoyaltyManager()->addLoyalty($event->getPlayer(), LoyaltyCause::DEATH);

        $this->main->getLoyaltyManager()->getTracker()->touch($event->getPlayer());
    }

    /** @noinspection PhpUnused */
    public function onEntityDamage(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        $victim  = $event->getEntity();
        if (! $damager instanceof Player || ! $victim instanceof Player) {
            return;
        }

        $damagerSession = Session::get($damager);
        $victimSession  = Session::get($victim);

        if (! $damagerSession->isLoaded() || ! $victimSession->isLoaded()) {
            return;
        }

        $damagerKingdom = $damagerSession->getKingdom();
        $victimKingdom  = $victimSession->getKingdom();

        if ($damagerKingdom && $victimKingdom && $damagerKingdom->id === $victimKingdom->id) {
            $loyaltyManager = $this->main->getLoyaltyManager();

            if (! $loyaltyManager->canBetray($damager)) {
                $event->cancel();
                return;
            }

            if ($victim->getHealth() - $event->getFinalDamage() <= 0) {
                $loyaltyManager->addLoyalty($damager, LoyaltyCause::TEAM_ATTACK);
                MessagesUtils::sendTo($damager, MessagesIds::BETRAYAL_KILL);
                // TODO: Add special betrayal rewards
            }
        } else if ($damagerKingdom && $victimKingdom && $damagerKingdom->id !== $victimKingdom->id) {
            if ($victim->getHealth() - $event->getFinalDamage() <= 0) {
                if ($victimKingdom->getBase()->isPlayerInBase($victim)) {
                    $this->main->getLoyaltyManager()->addLoyalty($damager, LoyaltyCause::DEFENSE_SUCCESS);
                } else {
                    $this->main->getLoyaltyManager()->addLoyalty($damager, LoyaltyCause::ENEMY_KILL);
                }
            }
        }
    }

    /** @noinspection PhpUnused */
    public function onQuit(PlayerCombatLogoutEvent $event): void
    {
        $this->main->getLoyaltyManager()->addLoyalty($event->getPlayer(), LoyaltyCause::COMBAT_LOG);
    }

    /** @noinspection PhpUnused */
    public function onMove(PlayerMoveEvent $event): void
    {
        if ($event->getFrom()->distance($event->getTo()) > 0.1) {
            $this->main->getLoyaltyManager()->getTracker()->touch($event->getPlayer());
        }
    }

    /** @noinspection PhpUnused */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $this->main->getLoyaltyManager()->getTracker()->touch($event->getPlayer());
    }

}