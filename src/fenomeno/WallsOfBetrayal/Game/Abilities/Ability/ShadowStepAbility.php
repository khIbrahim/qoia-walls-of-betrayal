<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Ability;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Game\Abilities\BaseAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\UseAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\CooldownManager;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;

class ShadowStepAbility extends BaseAbility implements UseAbilityInterface
{
    public function getId(): string { return "shadow_step"; }
    public function getName(): string { return "Shadow Step"; }
    public function getDescription(): string {return "§7Teleport instantly §e10 blocks§7 in the direction you're facing.\n§7Cooldown: §c3 minutes";}
    public function getIcon(?Player $player = null): Item {
        $item = VanillaItems::ENDER_PEARL();
        $item->setCustomName(TextFormat::RESET . $this->getColor() . $this->getName());

        $status = "§r§aREADY";
        if ($player !== null && Main::getInstance()->getAbilityManager()->isOnCooldown($player, $this->getId())) {
            $rem = Main::getInstance()->getAbilityManager()->getCooldownRemaining($player, $this->getId());
            $m = intdiv($rem, 60); $s = $rem % 60;
            $status = "§r§cON COOLDOWN §7({$m}m {$s}s)";
        }

        $lore = [
            "§r§7Blink forward §f10 blocks§7.",
            "§r§7Type: §fUse  §8|  §7Rarity: {$this->getRarity()->getColor()}Legendary",
            "§r§7Duration: §f—  §8|  §7Cooldown: §f3m 0s",
            "§r§8────────────────────────",
            "§r§6Status: $status",
            "§r§7Hint: Right‑click the bound item or use §f/ability use shadow_step",
            "§r§8────────────────────────",
            "§r§7Left‑click: §fDetails  §8|  §7Right‑click: §fAssign",
            "§r§7Command: §f/ability shadow_step"
        ];
        $item->setLore($lore);
        return $item;
    }
    public function getColor(): string { return TextFormat::DARK_PURPLE; }
    public function getRarity(): AbilityRarity { return AbilityRarity::LEGENDARY; }
    public function getUsageTime(): int { return 0; }
    public function getCooldown(): int { return 180; }

    public function onUse(Player $player): void
    {
        $this->onEnable($player);
        if (CooldownManager::isOnCooldown($this->getId(), $player->getName())) {
            $remaining = CooldownManager::getCooldownRemaining($this->getId(), $player->getName());
            $this->sendCooldownMessage($player, $remaining);
            return;
        }

        $direction = $player->getDirectionVector()->normalize();
        $d = $direction->multiply(10);
        $target = $player->getPosition()->add($d->x, $d->y, $d->z);

        $world = $player->getWorld();
        $finalTarget = $target;
        for ($i = 9; $i >= 0; $i--) {
            $d = $direction->multiply($i);
            $testPos = $player->getPosition()->add($d->x, $d->y, $d->z);
            if ($world->getBlock($testPos->floor())->isSolid()) continue;
            $finalTarget = $testPos;
            break;
        }

        $player->teleport($finalTarget);
        $player->getWorld()->addSound($finalTarget, new EndermanTeleportSound());
        $this->displayVisualEffects($player);

        MessagesUtils::sendTo($player, 'abilities.shadow_step.success', [
            '{ABILITY}' => $this->getName()
        ]);

        CooldownManager::setCooldown($this->getId(), $player->getName(), $this->getCooldown());
    }

    public function displayVisualEffects(Player $player): void
    {
        $start = $player->getPosition();
        $direction = $player->getDirectionVector()->normalize();

        for ($i = 1; $i <= 10; $i++) {
            $d = $direction->multiply($i);
            $pos = $start->add($d->x, $d->y, $d->z);
            $player->getWorld()->addParticle($pos, new EndermanTeleportParticle());
        }
    }

    public function tick(Player $player): bool
    {
        return true;
    }
}
