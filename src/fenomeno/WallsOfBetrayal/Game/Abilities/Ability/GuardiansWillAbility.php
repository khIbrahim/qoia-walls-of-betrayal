<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Ability;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Enum\LoyaltyRank;
use fenomeno\WallsOfBetrayal\Game\Abilities\BaseAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\ConditionalAbilityInterface;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\DefenseAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\EnchantParticle;

class GuardiansWillAbility extends BaseAbility implements DefenseAbilityInterface, ConditionalAbilityInterface
{
    public function getId(): string
    {
        return "guardians_will";
    }

    public function getName(): string
    {
        return "Guardian's Will";
    }

    public function getDescription(): string
    {
        return "§7Loyal guardians gain §bResistance II §7and §eRegeneration §7\n§7when defending their kingdom territory\n§7Duration: §e20 seconds\n§7Cooldown: §c8 minutes\n§cRequires: §aLoyal §crank or higher";
    }

    public function getIcon(?Player $player = null): Item 
    {
        $item = VanillaItems::DIAMOND();
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
            "§r§7Gain §bResistance II §7and §eRegeneration",
            "§r§7when defending kingdom territory.",
            "§r§7Type: §fDefense  §8|  §7Rarity: {$this->getRarity()->getColor()}Legendary",
            "§r§7Duration: §f20s  §8|  §7Cooldown: §f8m 0s",
            "§r§8────────────────────────",
            "§r§6Status: $status",
            "§r§6Loyalty: $loyaltyStatus",
            "§r§7Trigger: §fDefend against enemies in your territory.",
            "§r§8────────────────────────",
            "§r§7Left‑click: §fDetails  §8|  §7Right‑click: §fAssign",
            "§r§7Command: §f/ability guardians_will"
        ];
        $item->setLore($lore);

        return $item;
    }

    public function getColor(): string
    {
        return "§b";
    }

    public function getRarity(): AbilityRarity
    {
        return AbilityRarity::LEGENDARY;
    }

    public function onEnable(Player $player): void
    {
        parent::onEnable($player);
        
        // Apply defense effects
        $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 400, 1)); // 20 seconds, level 2
        $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 400, 0)); // 20 seconds, level 1
        
        $this->sendActivationMessage($player);
    }

    public function onDisable(Player $player): void
    {
        parent::onDisable($player);
        
        // Remove effects when ability ends
        $player->getEffects()->remove(VanillaEffects::RESISTANCE());
        $player->getEffects()->remove(VanillaEffects::REGENERATION());
        
        $player->sendMessage("§b§lGuardian's Will §7has worn off.");
    }

    public function tick(Player $player): bool
    {
        // Create enchant particles around the player
        $pos = $player->getPosition();
        $world = $player->getWorld();
        
        for ($i = 0; $i < 3; $i++) {
            $x = $pos->x + (mt_rand(-20, 20) / 10);
            $y = $pos->y + mt_rand(0, 20) / 10;
            $z = $pos->z + (mt_rand(-20, 20) / 10);
            
            $world->addParticle($pos->asVector3()->add($x - $pos->x, $y - $pos->y, $z - $pos->z), new EnchantParticle());
        }

        return true;
    }

    public function getUsageTime(): int
    {
        return 20; // 20 seconds
    }

    public function getCooldown(): int
    {
        return 480; // 8 minutes
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

    public function onDefense(Player $defender, Player $attacker): void
    {
        // This will be called by the AbilityManager when a player defends
        // Check if the defender has this ability and it's not on cooldown
        $abilityManager = Main::getInstance()->getAbilityManager();
        
        if ($abilityManager->hasAbility($defender, $this->getId()) && 
            !$abilityManager->isOnCooldown($defender, $this->getId())) {
            
            // Trigger the ability
            $abilityManager->triggerAbility($defender, $this);
            
            // Set cooldown
            Main::getInstance()->getCooldownManager()->setCooldown($this->getId(), $defender->getName(), $this->getCooldown());
        }
    }
}