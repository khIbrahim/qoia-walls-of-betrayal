<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Ability;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Game\Abilities\BaseAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\UseAbilityInterface;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\SnowballPoofParticle;
use pocketmine\world\sound\TotemUseSound;

class NightVeilAbility extends BaseAbility implements UseAbilityInterface
{
    /** @var array<string, int> */
    private array $lastUsedNight = [];

    public function getId(): string { return "night_veil"; }
    public function getName(): string { return "Night Veil"; }
    public function getDescription(): string {
        return "§7Gain §dInvisibility§7 for §e10 seconds§7 at night.\n§7Can be used §cone time per night§7.";
    }
    public function getIcon(): string { return "textures/items/ender_eye"; }
    public function getColor(): string { return TextFormat::LIGHT_PURPLE; }
    public function getRarity(): AbilityRarity { return AbilityRarity::EPIC; }
    public function getUsageTime(): int { return 10; }
    public function getCooldown(): int { return 0; }

    public function onUse(Player $player): void
    {
        $world = $player->getWorld();
        $time = $world->getTime() % 24000;
        $isNight = ($time > 13000 && $time < 23000);

        $nightId = intval(floor($world->getTime() / 24000));

        if (! $isNight) {
            MessagesUtils::sendTo($player, "abilities.night_veil.day");
            $player->sendActionBarMessage("§7Night Veil can only be used at night!");
            return;
        }

        if (($this->lastUsedNight[$player->getName()] ?? -1) === $nightId) {
            MessagesUtils::sendTo($player, "abilities.night_veil.once");
            $player->sendActionBarMessage("§7Already used this night.");
            return;
        }

        $this->lastUsedNight[$player->getName()] = $nightId;

        $player->getEffects()->add(new EffectInstance(
            VanillaEffects::INVISIBILITY(),
            20 * $this->getUsageTime(),
            0,
            false
        ));

        $this->displayVisualEffects($player);

        MessagesUtils::sendTo($player, 'abilities.night_veil.success', [
            '{ABILITY}' => $this->getName()
        ]);
        $player->getWorld()->addSound($player->getPosition(), new TotemUseSound());
    }

    public function displayVisualEffects(Player $player): void
    {
        $center = $player->getPosition();
        for ($i = 0; $i < 360; $i += 15) {
            $x = cos(deg2rad($i)) * 2;
            $z = sin(deg2rad($i)) * 2;
            $pos = $center->add($x, 1, $z);
            $player->getWorld()->addParticle($pos, new SnowballPoofParticle());
        }
    }

    public function tick(Player $player): bool
    {
        return true;
    }

}