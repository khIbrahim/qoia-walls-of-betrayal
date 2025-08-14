<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Ability;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Game\Abilities\BaseAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\KillAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\particle\RedstoneParticle;

class BloodRageAbility extends BaseAbility implements KillAbilityInterface
{
    private array $activeEffects = [];

    public function getId(): string
    {
        return "blood_rage";
    }

    public function getName(): string
    {
        return "Blood Rage";
    }

    public function getDescription(): string
    {
        return "§7Gain §c+50% strength §7after eliminating an enemy\n§7Duration: §e13 seconds\n§7Cooldown: §c5 minutes";
    }

    public function getIcon(?Player $player = null): Item {
        $item = VanillaItems::REDSTONE_DUST();
        $item->setCustomName(TextFormat::RESET . $this->getColor() . $this->getName());

        $status = "§r§aREADY";
        if ($player !== null && Main::getInstance()->getAbilityManager()->isOnCooldown($player, $this->getId())) {
            $rem = Main::getInstance()->getAbilityManager()->getCooldownRemaining($player, $this->getId());
            $m = intdiv($rem, 60); $s = $rem % 60;
            $status = "§r§cON COOLDOWN §7({$m}m {$s}s)";
        }

        $lore = [
            "§r§7Gain §c+50% strength §7after a kill.",
            "§r§7Type: §fOn‑Kill  §8|  §7Rarity: {$this->getRarity()->getColor()}Epic",
            "§r§7Duration: §f13s  §8|  §7Cooldown: §f5m 0s",
            "§r§8────────────────────────",
            "§r§6Status: $status",
            "§r§7Trigger: §fEliminate a player to activate.",
            "§r§8────────────────────────",
            "§r§7Left‑click: §fDetails  §8|  §7Right‑click: §fAssign",
            "§r§7Command: §f/ability blood_rage"
        ];
        $item->setLore($lore);
        return $item;
    }

    public function getColor(): string{return TextFormat::RED;}
    public function getRarity(): AbilityRarity{return AbilityRarity::EPIC;}
    public function getUsageTime(): int{return 13;}
    public function getCooldown(): int{return 5 * 60;}

    public function onEnable(Player $player): void
    {
        parent::onEnable($player);
        $this->activeEffects[$player->getName()] = time();

        $player->getEffects()->add(new EffectInstance(
            VanillaEffects::STRENGTH(),
            20 * $this->getUsageTime(),
            1,
            true
        ));

        $this->displayVisualEffects($player);
    }

    public function onDisable(Player $player): void
    {
        unset($this->activeEffects[$player->getName()]);

        MessagesUtils::sendTo($player, 'abilities.disabled', [
            '{ABILITY}' => $this->getName(),
            '{COLOR}'   => $this->getColor()
        ]);
    }

    public function tick(Player $player): bool
    {
        if (! isset($this->activeEffects[$player->getName()])) {
            $this->onDisable($player);
            return false;
        }

        $elapsed = time() - $this->activeEffects[$player->getName()];
        if ($elapsed >= $this->getUsageTime()) {
            $this->onDisable($player);
            return false;
        }

        $elapsed = time() - $this->activeEffects[$player->getName()];
        $remaining = $this->getUsageTime() - $elapsed;

        if ($remaining > 0) {
            $this->sendProgressBar($remaining, $player);

            if ($elapsed % 2 === 0) {
                $this->spawnBloodParticles($player);
            }
        }

        return true;
    }

    public function onKill(Player $killer, Player $victim): void
    {
        if (Main::getInstance()->getCooldownManager()->isOnCooldown($this->getId(), $killer->getName())) {
            $remaining = Main::getInstance()->getCooldownManager()->getCooldownRemaining($this->getId(), $killer->getName());
            $this->sendCooldownMessage($killer, $remaining);
            return;
        }

        $this->onEnable($killer);
        $this->sendActivationMessage($killer);

        Main::getInstance()->getCooldownManager()->setCooldown($this->getId(), $killer->getName(), $this->getCooldown());

        foreach ($killer->getWorld()->getNearbyEntities($killer->getBoundingBox()->expandedCopy(20, 20, 20)) as $entity) {
            if ($entity instanceof Player && $entity !== $killer) {
                MessagesUtils::sendTo($entity, 'abilities.blood_rage.victimEnter', [
                    '{KILLER}' => $killer->getName()
                ]);
            }
        }
    }

    public function displayVisualEffects(Player $player): void
    {
        for ($i = 0; $i < 360; $i += 30) {
            $x = cos(deg2rad($i)) * 2;
            $z = sin(deg2rad($i)) * 2;

            $pos = $player->getPosition()->add($x, 1, $z);
            $player->getWorld()->addParticle($pos, new RedstoneParticle());
        }

        for ($y = 0; $y < 3; $y += 0.3) {
            $pos = $player->getPosition()->add(0, $y, 0);
            $player->getWorld()->addParticle($pos, new LavaParticle());
        }
    }

    private function spawnBloodParticles(Player $player): void
    {
        for ($i = 0; $i < 3; $i++) {
            $x = mt_rand(-10, 10) / 10;
            $z = mt_rand(-10, 10) / 10;
            $y = mt_rand(5, 15) / 10;

            $pos = $player->getPosition()->add($x, $y, $z);
            $player->getWorld()->addParticle($pos, new RedstoneParticle());
        }
    }

}