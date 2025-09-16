<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Events\LoyaltyChangeEvent;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\DefenseAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\LoyaltyManager;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class LoyaltyListener implements Listener
{
    public function __construct(private readonly Main $main) {}

    /**
     * Handle player death - decrease loyalty
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $this->main->getLoyaltyManager()->addLoyalty(
            $player, 
            LoyaltyManager::PLAYER_DEATH, 
            'Player death'
        );
    }

    /**
     * Track player activity for AFK detection
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        if ($event->getFrom()->distance($event->getTo()) > 0.1) {
            $this->main->getLoyaltyManager()->updateActivity($event->getPlayer());
        }
    }

    /**
     * Track player activity on interaction
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $this->main->getLoyaltyManager()->updateActivity($event->getPlayer());
    }

    /**
     * Handle team damage/kills - loyalty penalty
     */
    public function onEntityDamage(EntityDamageByEntityEvent $event): void
    {
        if (!($event->getDamager() instanceof Player) || !($event->getEntity() instanceof Player)) {
            return;
        }

        $damager = $event->getDamager();
        $victim = $event->getEntity();

        // Check if they're from the same kingdom (team kill)
        $damagerSession = Session::get($damager);
        $victimSession = Session::get($victim);

        if (!$damagerSession->isLoaded() || !$victimSession->isLoaded()) {
            return;
        }

        $damagerKingdom = $damagerSession->getKingdom();
        $victimKingdom = $victimSession->getKingdom();

        if ($damagerKingdom && $victimKingdom && $damagerKingdom->id === $victimKingdom->id) {
            // Same kingdom - check if betrayal is allowed
            $loyaltyManager = $this->main->getLoyaltyManager();
            
            if (!$loyaltyManager->canBetray($damager)) {
                // Not allowed to betray - prevent damage and apply penalty
                $event->cancel();
                $loyaltyManager->addLoyalty($damager, LoyaltyManager::TEAM_KILL, 'Team damage attempt');
                $damager->sendMessage("§cYou cannot attack your allies! Loyalty penalty applied.");
                return;
            }

            // Betrayal is allowed - award loyalty for successful betrayal
            if ($victim->getHealth() - $event->getFinalDamage() <= 0) {
                $loyaltyManager->addLoyalty($damager, -LoyaltyManager::TEAM_KILL, 'Betrayal kill');
                $damager->sendMessage("§4§lBETRAYAL KILL! §cSpecial rewards earned.");
                // TODO: Add special betrayal rewards here
            }
        } else if ($damagerKingdom && $victimKingdom && $damagerKingdom->id !== $victimKingdom->id) {
            // Enemy kill - award loyalty
            if ($victim->getHealth() - $event->getFinalDamage() <= 0) {
                $this->main->getLoyaltyManager()->addLoyalty($damager, LoyaltyManager::ENEMY_KILL, 'Enemy kill');
            }

            // Trigger defense abilities for the victim (defending)
            $this->main->getAbilityManager()->triggerAbilityType($victim, DefenseAbilityInterface::class, $victim, $damager);
            
            // Check if this counts as a successful defense (victim survives and is in their territory)
            if ($victim->getHealth() - $event->getFinalDamage() > 0) {
                // TODO: Add territory checking logic here
                // For now, award defense loyalty if the victim survives
                $this->main->getLoyaltyManager()->addLoyalty($victim, LoyaltyManager::SUCCESSFUL_DEFENSE, 'Successful defense');
            }
        }
    }

    /**
     * Handle combat tag logout penalty
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        
        // Check if player is in combat
        if ($this->main->getCombatManager()->isInCombat($player)) {
            $this->main->getLoyaltyManager()->addLoyalty(
                $player, 
                LoyaltyManager::COMBAT_TAG_LOGOUT, 
                'Combat tag logout'
            );
        }

        // Clean up loyalty manager data
        $this->main->getLoyaltyManager()->cleanup($player);
    }

    /**
     * Handle loyalty change events for debugging/logging
     */
    public function onLoyaltyChange(LoyaltyChangeEvent $event): void
    {
        $player = $event->getPlayer();
        $change = $event->getChange();
        $reason = $event->getReason();
        
        $this->main->getLogger()->debug("Loyalty change for {$player->getName()}: {$change} ({$reason})");
    }
}