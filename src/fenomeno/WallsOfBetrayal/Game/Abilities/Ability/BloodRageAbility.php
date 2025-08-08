<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Ability;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\KillAbilityInterface;
use fenomeno\WallsOfBetrayal\Utils\CooldownManager;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\particle\RedstoneParticle;
use fenomeno\WallsOfBetrayal\Game\Abilities\BaseAbility;

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

    public function getIcon(): string
    {
        return "textures/items/redstone_dust";
    }

    public function getColor(): string
    {
        return TextFormat::RED;
    }

    public function getRarity(): AbilityRarity
    {
        return AbilityRarity::EPIC;
    }

    public function getUsageTime(): int
    {
        return 13;
    }

    public function getCooldown(): int
    {
        return 5 * 60;
    }

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
        if (CooldownManager::isOnCooldown($this->getId(), $killer->getName())) {
            $remaining = CooldownManager::getCooldownRemaining($this->getId(), $killer->getName());
            $this->sendCooldownMessage($killer, $remaining);
            return;
        }

        $this->onEnable($killer);
        $this->sendActivationMessage($killer);

        CooldownManager::setCooldown($this->getId(), $killer->getName(), $this->getCooldown());

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