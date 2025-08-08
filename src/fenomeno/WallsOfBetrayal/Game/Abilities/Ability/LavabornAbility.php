<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Ability;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Game\Abilities\BaseAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\UseAbilityInterface;
use fenomeno\WallsOfBetrayal\Utils\CooldownManager;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\particle\FlameParticle;

class LavabornAbility extends BaseAbility implements UseAbilityInterface
{
    private array $activeEffects = [];

    public function getId(): string
    {
        return "lavaborn";
    }

    public function getName(): string
    {
        return "Lavaborn";
    }

    public function getDescription(): string
    {
        return "§7Immune to §cfire§7 & §6lava§7 for §e10 seconds§7.\n§7Cooldown: §c5 minutes";
    }

    public function getIcon(): string
    {
        return "textures/items/lava_bucket";
    }

    public function getColor(): string
    {
        return TextFormat::GOLD;
    }

    public function getRarity(): AbilityRarity
    {
        return AbilityRarity::EPIC;
    }

    public function getUsageTime(): int
    {
        return 10;
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
            VanillaEffects::FIRE_RESISTANCE(),
            20 * $this->getUsageTime(),
            1,
            true
        ));

        $this->displayVisualEffects($player);
    }

    public function onDisable(Player $player): void
    {
        unset($this->activeEffects[$player->getName()]);

        $player->getEffects()->remove(VanillaEffects::FIRE_RESISTANCE());

        MessagesUtils::sendTo($player, 'abilities.disabled', [
            '{ABILITY}' => $this->getName(),
            '{COLOR}'   => $this->getColor()
        ]);
    }

    public function tick(Player $player): bool
    {
        if (! isset($this->activeEffects[$player->getName()])) {
            return false;
        }

        $elapsed = time() - $this->activeEffects[$player->getName()];
        if ($elapsed >= $this->getUsageTime()) {
            $this->onDisable($player);
            return false;
        }

        $remaining = $this->getUsageTime() - $elapsed;

        if ($remaining > 0) {
            $this->sendProgressBar($remaining, $player);

            if ($elapsed % 2 === 0) {
                $this->spawnLavaParticles($player);
            }
        }

        return true;
    }

    public function onUse(Player $player): void
    {
        if (CooldownManager::isOnCooldown($this->getId(), $player->getName())) {
            $remaining = CooldownManager::getCooldownRemaining($this->getId(), $player->getName());
            $this->sendCooldownMessage($player, $remaining);
            return;
        }

        $this->onEnable($player);
        $this->sendActivationMessage($player);

        CooldownManager::setCooldown($this->getId(), $player->getName(), $this->getCooldown());
    }

    public function displayVisualEffects(Player $player): void
    {
        for ($i = 0; $i < 360; $i += 30) {
            $x = cos(deg2rad($i)) * 2;
            $z = sin(deg2rad($i)) * 2;
            $pos = $player->getPosition()->add($x, 1, $z);
            $player->getWorld()->addParticle($pos, new LavaParticle());
        }
        for ($i = 0; $i < 10; $i++) {
            $x = mt_rand(-10, 10) / 10;
            $z = mt_rand(-10, 10) / 10;
            $y = mt_rand(5, 15) / 10;
            $pos = $player->getPosition()->add($x, $y, $z);
            $player->getWorld()->addParticle($pos, new FlameParticle());
        }
    }

    private function spawnLavaParticles(Player $player): void
    {
        for ($i = 0; $i < 3; $i++) {
            $x = mt_rand(-10, 10) / 10;
            $z = mt_rand(-10, 10) / 10;
            $y = mt_rand(5, 15) / 10;

            $pos = $player->getPosition()->add($x, $y, $z);
            $player->getWorld()->addParticle($pos, new LavaParticle());
        }
    }
}
