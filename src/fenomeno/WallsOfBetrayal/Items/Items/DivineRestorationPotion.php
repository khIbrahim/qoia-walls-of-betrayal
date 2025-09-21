<?php

namespace fenomeno\WallsOfBetrayal\Items\Items;

use customiesdevs\customies\item\component\CanDestroyInCreativeComponent;
use customiesdevs\customies\item\component\FoilComponent;
use customiesdevs\customies\item\component\FoodComponent;
use customiesdevs\customies\item\component\GlowingComponent;
use customiesdevs\customies\item\component\HandEquippedComponent;
use customiesdevs\customies\item\component\LiquidClippedComponent;
use customiesdevs\customies\item\component\MaxStackSizeComponent;
use customiesdevs\customies\item\component\StackedByDataComponent;
use customiesdevs\customies\item\component\UseAnimationComponent;
use customiesdevs\customies\item\component\UseDurationComponent;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\entity\Consumable;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\Potion;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\BottleEmptySound;
use Throwable;

class DivineRestorationPotion extends Potion implements ItemComponents, Consumable
{
    use ItemComponentsTrait;

    public const IDENTIFIER = "wob:divine_restoration";


    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::newId()), "Divine Restoration Potion");
        $this->initComponent('divine_restoration_potion');

        $this->setCustomName("§d§lDivine Restoration Potion");

        $this->setLore([
            "§r§r",
            "§r§d✧ A powerful divine elixir ✧",
            "§r§r",
            "§r§7• Restores 50% HP",
            "§r§7• Heals nearby allies and grant loyalty score",
            "§r§7• Grants beneficial effects",
            "§r§7• Cleanses negative effects",
            "§r§r",
            "§r§e2 minute cooldown",
        ]);

        $this->addComponent(new FoilComponent(true));
        $this->addComponent(new UseDurationComponent(32));
        $this->addComponent(new UseAnimationComponent(UseAnimationComponent::ANIMATION_DRINK));
        $this->addComponent(new HandEquippedComponent(true));
        $this->addComponent(new FoodComponent(
            canAlwaysEat: true,
            nutrition: 0,
            saturationModifier: 0.1,
            usingConvertsTo: "glass_bottle"
        ));
        $this->addComponent(new CanDestroyInCreativeComponent(false));
        $this->addComponent(new MaxStackSizeComponent(64));
        $this->addComponent(new StackedByDataComponent(true));
        $this->addComponent(new LiquidClippedComponent(true));
        $this->addComponent(new GlowingComponent("#AA00AA"));
    }

    public function onConsume(Living $consumer): void
    {
        if (! $consumer instanceof Player){
            return;
        }

        $this->applyEffects($consumer);

        $minX    = $consumer->getPosition()->getX() - 5;
        $maxX    = $consumer->getPosition()->getX() + 5;
        $minY    = $consumer->getPosition()->getY() - 5;
        $maxY    = $consumer->getPosition()->getY() + 5;
        $minZ    = $consumer->getPosition()->getZ() - 5;
        $maxZ    = $consumer->getPosition()->getZ() + 5;

        for($i = 0; $i < 2; $i++) {
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($consumer) {
                for($angle = 0; $angle < 360; $angle += 15) {
                    $x = cos(deg2rad($angle)) * 3;
                    $z = sin(deg2rad($angle)) * 3;
                    $pos = $consumer->getPosition()->add($x, 0.5, $z);
                    $consumer->getWorld()->addParticle($pos, new HugeExplodeParticle());
                }
            }), 20 * $i);
        }

        $players = $consumer->getWorld()->getNearbyEntities(new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ), $consumer);
        foreach ($players as $p){
            if (! $p instanceof Player){
                continue;
            }

            if ($p->getHealth() > 10){
                continue;
            }

            if ($p->getName() === $consumer->getName()){
                continue;
            }

            $pSession = Session::get($p);
            $cSession = Session::get($consumer);
            if (! $pSession->isLoaded() || ! $cSession->isLoaded() || $pSession->getKingdom()?->id !== $cSession->getKingdom()?->id){
                continue;
            }

            $beforeHealth = $p->getHealth();
            $healAmount   = (int) ($p->getMaxHealth() * 0.5);
            $this->applyEffects($p);
            $consumer->broadcastSound(new AnvilUseSound());
            $p->getWorld()->addParticle($p->getPosition()->add(0, 0.5, 0), new HugeExplodeParticle());
            MessagesUtils::sendTo($p, MessagesIds::DIVINE_RESTORATION_HEALED_BY_OTHER, [ExtraTags::PLAYER => $consumer->getName()]);
            Await::g2c(
                Main::getInstance()->getLoyaltyManager()->grantHealLoyalty($consumer, $p, $healAmount, $beforeHealth),
                fn() => null,
                fn(Throwable $e) => Utils::onFailure($e, $consumer, "Failed to grant heal loyalty by healing {$p->getName()} using Divine Restoration Potion")
            );
        }

        $consumer->getWorld()->addParticle($consumer->getPosition()->add(0, 0.5, 0), new HugeExplodeParticle());
        $consumer->broadcastSound(new BottleEmptySound());

        Main::getInstance()->getCooldownManager()->setCooldown(self::IDENTIFIER, $consumer->getName(), 60 * 2);

        $item = clone $this->pop();
        $consumer->getInventory()->setItemInHand($item);
    }

    public function applyEffects(Player $player): void
    {
        $maxHealth  = $player->getMaxHealth();
        $healAmount = (int) ($maxHealth * 0.5);
        $player->setHealth(min($maxHealth, $player->getHealth() + $healAmount));

        $effects = [
            new EffectInstance(VanillaEffects::REGENERATION(), 600, 1),
            new EffectInstance(VanillaEffects::ABSORPTION(), 1200, 0),
            new EffectInstance(VanillaEffects::RESISTANCE(), 1200, 0),
            new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 1200, 0),
        ];
        foreach ($effects as $effect) {
            $player->getEffects()->add($effect);
        }

        foreach ($player->getEffects()->all() as $effect) {
            if ($effect->getType()->isBad()) {
                $player->getEffects()->remove($effect->getType());
            }
        }

        MessagesUtils::sendTo($player, MessagesIds::DIVINE_RESTORATION_HEALED, [ExtraTags::HEAL => $healAmount]);
    }

    public function canStartUsingItem(Player $player): bool
    {
        $inCooldown = Main::getInstance()->getCooldownManager()->isOnCooldown(self::IDENTIFIER, $player->getName());

        if ($inCooldown){
            MessagesUtils::sendTo($player, MessagesIds::DIVINE_RESTORATION_COOLDOWN, [
                ExtraTags::TIME => Main::getInstance()->getCooldownManager()->getCooldownRemaining(self::IDENTIFIER, $player->getName(), true)
            ]);
        }

        return ! $inCooldown;
    }

    public function getMaxStackSize(): int
    {
        return 64;
    }
//
//    public function setUseDuration()
//    {
//
//    }

}