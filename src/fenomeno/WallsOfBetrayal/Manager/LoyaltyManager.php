<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Player\PlayerLoyalty;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\UpdatePlayerLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Enum\LoyaltyRank;
use fenomeno\WallsOfBetrayal\Events\LoyaltyChangeEvent;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\player\Player;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;

final class LoyaltyManager
{
    // Loyalty change amounts
    public const SUCCESSFUL_DEFENSE = 5;
    public const ENEMY_KILL = 2;
    public const ACTIVE_PLAYTIME = 1;
    public const CONTRIBUTION_BASE = 1;
    public const QUEST_COMPLETION = 3;        // New: Complete kingdom quests
    public const RESOURCE_GATHERING = 1;     // New: Gather resources for kingdom
    public const ALLIANCE_HELP = 2;          // New: Help allied kingdoms

    public const PLAYER_DEATH = -2;
    public const COMBAT_TAG_LOGOUT = -5;
    public const TEAM_KILL = -15;
    public const AFK_PENALTY = -1;
    public const DESERTION = -10;            // New: Leave kingdom during war
    public const RESOURCE_THEFT = -5;        // New: Steal from kingdom treasury
    public const INSUBORDINATION = -3;       // New: Disobey kingdom leadership

    // Time intervals (in seconds)
    public const ACTIVE_PLAYTIME_INTERVAL = 1800; // 30 minutes
    public const AFK_CHECK_INTERVAL = 600;         // 10 minutes

    private array $lastActivity = [];
    private array $afkPlayers = [];

    public function __construct(private readonly Main $main) {}

    /**
     * Add loyalty to a player with reason and event handling
     */
    public function addLoyalty(Player $player, int $amount, string $reason = ''): void
    {
        $session = Session::get($player);
        if (!$session->isLoaded()) {
            return;
        }

        $loyalty = $session->getLoyalty();
        if (!$loyalty) {
            return;
        }

        $oldScore = $loyalty->loyaltyScore;
        $oldRank = LoyaltyRank::fromScore($oldScore);
        
        $newScore = max(0, min(100, $oldScore + $amount));
        $newRank = LoyaltyRank::fromScore($newScore);

        // Update loyalty in session
        $loyalty->loyaltyScore = $newScore;

        // Fire event
        $event = new LoyaltyChangeEvent($player, $oldScore, $newScore, $reason);
        $event->call();

        if ($event->isCancelled()) {
            $loyalty->loyaltyScore = $oldScore; // Revert
            return;
        }

        // Save to database
        Await::f2c(function () use ($loyalty) {
            yield from $this->main->getDatabaseManager()
                ->getPlayerLoyaltyRepository()
                ->updateOrInsertLoyalty(new UpdatePlayerLoyaltyPayload(
                    $loyalty->uuid,
                    $loyalty->username,
                    $loyalty->kingdomId,
                    $loyalty->loyaltyScore,
                    $loyalty->contributionCount,
                    $loyalty->betrayalCount,
                    $loyalty->lastBetrayal
                ));
        });

        // Handle rank changes
        $this->handleRankChange($player, $oldRank, $newRank, $amount, $reason);
        
        // Update player nametag
        $this->updatePlayerNameTag($player);
    }

    /**
     * Handle rank changes with appropriate messages and effects
     */
    private function handleRankChange(Player $player, LoyaltyRank $oldRank, LoyaltyRank $newRank, int $amount, string $reason): void
    {
        if ($oldRank === $newRank) {
            // Same rank, just show score change
            if ($amount !== 0) {
                $this->sendLoyaltyFeedback($player, $amount, $reason);
            }
            return;
        }

        // Rank changed - special handling
        $this->sendRankChangeMessage($player, $oldRank, $newRank);
        $this->playRankChangeEffects($player, $newRank);

        // Special cases
        if ($newRank === LoyaltyRank::TRAITOR && $oldRank !== LoyaltyRank::TRAITOR) {
            $this->activateBetrayalMode($player);
        } elseif ($oldRank === LoyaltyRank::TRAITOR && $newRank !== LoyaltyRank::TRAITOR) {
            $this->deactivateBetrayalMode($player);
        }
    }

    /**
     * Send loyalty change feedback to player
     */
    private function sendLoyaltyFeedback(Player $player, int $amount, string $reason): void
    {
        $session = Session::get($player);
        $loyalty = $session->getLoyalty();
        if (!$loyalty) return;

        $prefix = $amount > 0 ? "§a+" : "§c";
        $reasonText = $reason ? " §7($reason)" : "";
        
        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_SCORE_INCREASED, [
            '{SCORE}' => $loyalty->loyaltyScore,
            '{CHANGE}' => $prefix . $amount,
            '{REASON}' => $reasonText
        ]);
    }

    /**
     * Send rank change message
     */
    private function sendRankChangeMessage(Player $player, LoyaltyRank $oldRank, LoyaltyRank $newRank): void
    {
        $session = Session::get($player);
        $kingdom = $session->getKingdom();
        
        if ($newRank === LoyaltyRank::TRAITOR) {
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_LOW, [
                '{KINGDOM}' => $kingdom?->displayName ?? 'Unknown'
            ]);
        } elseif ($newRank === LoyaltyRank::SUSPECT) {
            $player->sendMessage("§cVos convictions envers votre royaume faiblissent…");
        } elseif ($newRank === LoyaltyRank::LOYAL) {
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_HIGH, [
                '{KINGDOM}' => $kingdom?->displayName ?? 'Unknown'
            ]);
        }
    }

    /**
     * Play sound and visual effects for rank changes
     */
    private function playRankChangeEffects(Player $player, LoyaltyRank $newRank): void
    {
        switch($newRank) {
            case LoyaltyRank::TRAITOR:
                $player->sendTitle("§4§lTRAITOR MODE ACTIVATED", "§cYou can now betray your allies", 10, 60, 20);
                $player->getWorld()->addSound($player->getLocation(), new NoteSound(NoteInstrument::BASS(), 1));
                break;
            case LoyaltyRank::LOYAL:
                $player->sendTitle("§a§lLOYAL", "§7Special abilities unlocked", 10, 40, 10);
                $player->getWorld()->addSound($player->getLocation(), new NoteSound(NoteInstrument::HARP(), 20));
                break;
            case LoyaltyRank::SUSPECT:
                $player->getWorld()->addSound($player->getLocation(), new ClickSound());
                break;
            default:
                break;
        }
    }

    /**
     * Activate betrayal mode for traitor players
     */
    private function activateBetrayalMode(Player $player): void
    {
        // The actual betrayal logic will be handled in combat listeners
        $player->sendMessage("§4§lBETRAYAL MODE: §cYou can now attack your allies and receive special rewards!");
    }

    /**
     * Deactivate betrayal mode
     */
    private function deactivateBetrayalMode(Player $player): void
    {
        $player->sendMessage("§a§lLOYALTY RESTORED: §7You can no longer betray your allies.");
    }

    /**
     * Get player's current loyalty rank
     */
    public function getLoyaltyRank(Player $player): ?LoyaltyRank
    {
        $session = Session::get($player);
        if (!$session->isLoaded()) {
            return null;
        }

        $loyalty = $session->getLoyalty();
        if (!$loyalty) {
            return null;
        }

        return LoyaltyRank::fromScore($loyalty->loyaltyScore);
    }

    /**
     * Get loyalty score as percentage
     */
    public function getLoyaltyPercentage(Player $player): int
    {
        $session = Session::get($player);
        if (!$session->isLoaded()) {
            return 50;
        }

        $loyalty = $session->getLoyalty();
        return $loyalty?->loyaltyScore ?? 50;
    }

    /**
     * Check if player can betray allies
     */
    public function canBetray(Player $player): bool
    {
        $rank = $this->getLoyaltyRank($player);
        return $rank?->canBetray() ?? false;
    }

    /**
     * Track player activity for AFK detection
     */
    public function updateActivity(Player $player): void
    {
        $this->lastActivity[$player->getName()] = time();
        
        // Remove from AFK list if they were AFK
        if (isset($this->afkPlayers[$player->getName()])) {
            unset($this->afkPlayers[$player->getName()]);
            $player->sendMessage("§aYou are no longer AFK.");
        }
    }

    /**
     * Check for AFK players and apply penalties
     */
    public function checkAFKPlayers(): void
    {
        $currentTime = time();
        
        foreach ($this->main->getServer()->getOnlinePlayers() as $player) {
            $lastActivity = $this->lastActivity[$player->getName()] ?? $currentTime;
            $timeSinceActivity = $currentTime - $lastActivity;
            
            if ($timeSinceActivity >= self::AFK_CHECK_INTERVAL) {
                if (!isset($this->afkPlayers[$player->getName()])) {
                    $this->afkPlayers[$player->getName()] = $currentTime;
                    $player->sendMessage("§eYou are now marked as AFK. Move to become active again.");
                }
                
                // Apply loyalty penalty every 10 minutes of AFK
                $afkTime = $currentTime - $this->afkPlayers[$player->getName()];
                $afkPenalties = intval($afkTime / self::AFK_CHECK_INTERVAL);
                
                if ($afkPenalties > 0) {
                    $this->addLoyalty($player, self::AFK_PENALTY * $afkPenalties, 'AFK penalty');
                    $this->afkPlayers[$player->getName()] = $currentTime; // Reset penalty timer
                }
            }
        }
    }

    /**
     * Award loyalty for active playtime
     */
    public function checkActivePlaytime(): void
    {
        foreach ($this->main->getServer()->getOnlinePlayers() as $player) {
            if (!isset($this->afkPlayers[$player->getName()])) {
                // Player is active, award loyalty
                $this->addLoyalty($player, self::ACTIVE_PLAYTIME, 'Active playtime');
            }
        }
    }

    /**
     * Calculate contribution loyalty bonus based on amount
     */
    public function calculateContributionBonus(int $contributionAmount): int
    {
        // Progressive bonus: more contribution = more loyalty
        if ($contributionAmount >= 10000) return 10;
        if ($contributionAmount >= 5000) return 7;
        if ($contributionAmount >= 1000) return 5;
        if ($contributionAmount >= 500) return 3;
        if ($contributionAmount >= 100) return 2;
        return self::CONTRIBUTION_BASE;
    }

    /**
     * Update player nametag with loyalty rank
     */
    public function updatePlayerNameTag(Player $player): void
    {
        $loyaltyRank = $this->getLoyaltyRank($player);
        $session = Session::get($player);
        
        if (!$loyaltyRank || !$session->isLoaded()) {
            return;
        }

        $baseDisplayName = $player->getName();
        $kingdomName = $session->getKingdom()?->displayName ?? "";
        
        // Format: [LOYAL] PlayerName [KingdomTag]
        $displayName = $loyaltyRank->getTag() . " §f" . $baseDisplayName;
        if ($kingdomName) {
            $displayName .= " §7[§r" . $kingdomName . "§7]";
        }
        
        $player->setDisplayName($displayName);
        $player->setNameTag($displayName);
    }

    /**
     * Clean up data for disconnected players
     */
    public function cleanup(Player $player): void
    {
        $name = $player->getName();
        unset($this->lastActivity[$name]);
        unset($this->afkPlayers[$name]);
    }
}