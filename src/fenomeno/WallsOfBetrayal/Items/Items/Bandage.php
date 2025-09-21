<?php

namespace fenomeno\WallsOfBetrayal\Items\Items;

use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use fenomeno\WallsOfBetrayal\Config\HealConfig;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\item\Durable;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\world\particle\HeartParticle;

class Bandage extends Durable implements ItemComponents
{
    use ItemComponentsTrait;
    
    protected const REGAIN_HEALTH_CAUSE = 5;
    
    public const IDENTIFIER = "wob:bandage";

    private int $selfCooldown;
    private int $otherCooldown;
    private int $minHealth;
    private int $healAmount;

    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::newId()), "Bandage");

        $this->setLore([
            "§r",
            "§r§7A simple bandage to heal minor wounds.",
            "§r",
            "§r§eUse on yourself or an ally to heal minor wounds to gain loyalty.",
            "§r§eCan only be used if you have §c" . (HealConfig::getBandageMinHealth() * 5) . "❤§e or less.",
            "§r",
            "§r§6Durability: §e5 uses",
            "§r§6Cooldown (on other): §e" . HealConfig::getBandageOnOtherCooldown() . "s",
            "§r§6Heal Amount: §e" . HealConfig::getBandageHeal() . "❤",
            "§r§6Loyalty Bonus: §e" . HealConfig::getBandageLoyaltyBonus() . " points",
            "§r",
        ]);

        $this->initComponent('bandage');

        $this->selfCooldown   = HealConfig::getBandageSelfCooldown();
        $this->otherCooldown  = HealConfig::getBandageOnOtherCooldown();
        $this->minHealth      = HealConfig::getBandageMinHealth();
        $this->healAmount     = HealConfig::getBandageHeal();
    }

    public function onInteractEntity(Player $player, Entity $entity, Vector3 $clickVector): bool
    {
        if ($entity instanceof Player) {
            $entitySession = Session::get($entity);
            $playerSession = Session::get($player);

            if (! $entitySession->isLoaded() || ! $playerSession->isLoaded()) {
                return false;
            }

            if ($entitySession->getKingdom()?->getId() !== $playerSession->getKingdom()?->getId()) {
                return false;
            }

            if (! Main::getInstance()->getCombatManager()->isTagged($entity)){
                MessagesUtils::sendTo($player, MessagesIds::BANDAGE_CANT_USE_ON_UNTAGGED);
                return false;
            }

            if (Main::getInstance()->getCooldownManager()->isOnCooldown(self::IDENTIFIER, $player->getName() . $entity->getName())) {
                MessagesUtils::sendTo($player, MessagesIds::BANDAGE_COOLDOWN_ON_OTHER, [
                    ExtraTags::TIME   => Main::getInstance()->getCooldownManager()->getCooldownRemaining(self::IDENTIFIER, $player->getName() . $entity->getName(), true),
                    ExtraTags::PLAYER => $entity->getName(),
                ]);
                return false;
            }

            if($entity->getHealth() > $this->minHealth){
                MessagesUtils::sendTo($player, MessagesIds::BANDAGE_CANT_USE_ON_LOW_HEALTH_OTHER, [
                    ExtraTags::HEALTH => HealConfig::getBandageMinHealth() * 5,
                ]);
                return false;
            }

            $beforeHealth = $entity->getHealth();
            $heal = $this->healAmount;
            $entity->heal(new EntityRegainHealthEvent($entity, $heal, self::REGAIN_HEALTH_CAUSE));
            $this->applyDamage(1);

            MessagesUtils::sendTo($player, MessagesIds::BANDAGE_USED_ON_OTHER, [
                ExtraTags::PLAYER => $entity->getName(),
                ExtraTags::HEAL   => $heal,
            ]);
            if ($player !== $entity) {
                MessagesUtils::sendTo($entity, MessagesIds::BANDAGE_USED_ON_YOU, [
                    ExtraTags::PLAYER => $player->getName(),
                    ExtraTags::HEAL   => $heal,
                ]);
            }

            /** @var Player $p */
            foreach ([$player, $entity] as $p) {
                $p->getNetworkSession()->sendDataPacket(PlaySoundPacket::create("item.bottle.fill", $p->getLocation()->x, $p->getLocation()->y, $p->getLocation()->z, 1.0, 1.0));
                $p->getWorld()->addParticle($p->getPosition(), new HeartParticle());
            }

            Main::getInstance()->getLoyaltyManager()->grantHealLoyalty($player, $entity, $this->healAmount, $beforeHealth);
            Main::getInstance()->getCooldownManager()->setCooldown(self::IDENTIFIER, $player->getName() . $entity->getName(), HealConfig::getBandageOnOtherCooldown());

            return true;
        }

        return false;
    }

    public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult
    {
        return $this->healSelf($player) ? ItemUseResult::SUCCESS() : ItemUseResult::FAIL();
    }

    public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems): ItemUseResult
    {
        return $this->healSelf($player) ? ItemUseResult::SUCCESS() : ItemUseResult::FAIL();
    }

    private function healSelf(Player $player): bool
    {
        if (Main::getInstance()->getCooldownManager()->isOnCooldown(self::IDENTIFIER, $player->getName())) {
            MessagesUtils::sendTo($player, MessagesIds::BANDAGE_COOLDOWN_ON_YOU, [
                ExtraTags::TIME => Main::getInstance()->getCooldownManager()->getCooldownRemaining(self::IDENTIFIER, $player->getName(), true),
            ]);
            return false;
        }

        if($player->getHealth() > HealConfig::getBandageMinHealth()){
            MessagesUtils::sendTo($player, MessagesIds::BANDAGE_CANT_USE_ON_LOW_HEALTH_SELF, [
                ExtraTags::HEALTH => HealConfig::getBandageMinHealth() * 5,
            ]);
            return false;
        }

        $heal = HealConfig::getBandageHeal();
        $player->heal(new EntityRegainHealthEvent($player, $heal, self::REGAIN_HEALTH_CAUSE));

        $this->applyDamage(1);

        MessagesUtils::sendTo($player, MessagesIds::BANDAGE_USED_ON_YOU, [ExtraTags::HEAL => $heal,]);
        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create("item.bottle.fill", $player->getLocation()->x, $player->getLocation()->y, $player->getLocation()->z, 1.0, 1.0));
        $player->getWorld()->addParticle($player->getPosition(), new HeartParticle());

        Main::getInstance()->getCooldownManager()->setCooldown(self::IDENTIFIER, $player->getName(), HealConfig::getBandageSelfCooldown());
        return true;
    }

    public function getMaxDurability(): int
    {
        return 5;
    }
}