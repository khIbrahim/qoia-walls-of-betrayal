<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Ability;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Game\Abilities\BaseAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\ConditionalAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\HappyVillagerParticle;

class RoyalBlessingAbility extends BaseAbility implements ConditionalAbilityInterface
{
    public function getId(): string
    {
        return "royal_blessing";
    }

    public function getName(): string
    {
        return "Royal Blessing";
    }

    public function getDescription(): string
    {
        return "§7Blessed by royalty, gain §eSpeed II§7, §aJump Boost§7,\n§7and §6absorption hearts §7for ultimate mobility\n§7Duration: §e15 seconds\n§7Cooldown: §c6 minutes\n§cRequires: §aLoyal §crank or higher";
    }

    public function getIcon(?Player $player = null): Item 
    {
        $item = VanillaItems::GOLDEN_APPLE();
        $item->setCustomName(TextFormat::RESET . $this->getColor() . $this->getName());

        $status = "§r§aREADY";
        if ($player !== null && Main::getInstance()->getAbilityManager()->isOnCooldown($player, $this->getId())) {
            $rem = Main::getInstance()->getAbilityManager()->getCooldownRemaining($player, $this->getId());
            $m = intdiv($rem, 60); $s = $rem % 60;
            $status = "§r§cON COOLDOWN §7({$m}m {$s}s)";
        }

        // Check loyalty requirement
        $loyaltyStatus = "§r§cREQUIRES LOYAL RANK";
        if ($player !== null) {
            $loyaltyRank = Main::getInstance()->getLoyaltyManager()->getLoyaltyRank($player);
            if ($loyaltyRank && $loyaltyRank->hasAbilityAccess()) {
                $loyaltyStatus = "§r§aLOYALTY REQUIREMENT MET";
            }
        }

        $lore = [
            "§r§7Gain §eSpeed II§7, §aJump Boost§7, and",
            "§r§7§6absorption hearts §7for mobility.",
            "§r§7Type: §fSelf‑Buff  §8|  §7Rarity: {$this->getRarity()->getColor()}Legendary",
            "§r§7Duration: §f15s  §8|  §7Cooldown: §f6m 0s",
            "§r§8────────────────────────",
            "§r§6Status: $status",
            "§r§6Loyalty: $loyaltyStatus",
            "§r§7Trigger: §fManually activate when needed.",
            "§r§8────────────────────────",
            "§r§7Left‑click: §fDetails  §8|  §7Right‑click: §fAssign",
            "§r§7Command: §f/ability royal_blessing"
        ];
        $item->setLore($lore);

        return $item;
    }

    public function getColor(): string
    {
        return "§6";
    }

    public function getRarity(): AbilityRarity
    {
        return AbilityRarity::LEGENDARY;
    }

    public function onEnable(Player $player): void
    {
        parent::onEnable($player);
        
        // Apply royal blessing effects
        $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 300, 1)); // 15 seconds, level 2
        $player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 300, 0)); // 15 seconds, level 1
        $player->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), 300, 1)); // 15 seconds, level 2 (4 extra hearts)
        
        $this->sendActivationMessage($player);
        
        // Award small loyalty bonus for using loyal ability
        Main::getInstance()->getLoyaltyManager()->addLoyalty($player, 1, 'Using Royal Blessing');
    }

    public function onDisable(Player $player): void
    {
        parent::onDisable($player);
        
        // Remove effects when ability ends
        $player->getEffects()->remove(VanillaEffects::SPEED());
        $player->getEffects()->remove(VanillaEffects::JUMP_BOOST());
        $player->getEffects()->remove(VanillaEffects::ABSORPTION());
        
        $player->sendMessage("§6§lRoyal Blessing §7has expired.");
    }

    public function tick(Player $player): bool
    {
        // Create happy villager particles around the player
        $pos = $player->getPosition();
        $world = $player->getWorld();
        
        for ($i = 0; $i < 2; $i++) {
            $x = $pos->x + (mt_rand(-15, 15) / 10);
            $y = $pos->y + mt_rand(10, 25) / 10;
            $z = $pos->z + (mt_rand(-15, 15) / 10);
            
            $world->addParticle($pos->asVector3()->add($x - $pos->x, $y - $pos->y, $z - $pos->z), new HappyVillagerParticle());
        }

        return true;
    }

    public function getUsageTime(): int
    {
        return 15; // 15 seconds
    }

    public function getCooldown(): int
    {
        return 360; // 6 minutes
    }

    public function getCost(): int
    {
        return 0; // Free for loyal players
    }

    public function checkCondition(Player $player): bool
    {
        // Check loyalty requirement
        $loyaltyRank = Main::getInstance()->getLoyaltyManager()->getLoyaltyRank($player);
        return $loyaltyRank && $loyaltyRank->hasAbilityAccess();
    }
}