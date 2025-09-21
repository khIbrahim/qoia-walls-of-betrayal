<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Config\HealConfig;
use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyCause;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\PotionType;
use pocketmine\item\SplashPotion as SplashPotionItem;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;

class ItemsListener implements Listener
{

    public function __construct(
        private readonly Main $main
    ){}

    public function onHit(ProjectileHitBlockEvent $event): void{
        $projectile = $event->getEntity();

        if($projectile instanceof SplashPotion){
            $player = $projectile->getOwningEntity();

            if($player instanceof Player){
                $distance = $projectile->getPosition()->distance($player->getPosition());

                if($distance <= 5.5 && $player->isAlive()){
                    $minX = $player->getPosition()->x - 5.5;
                    $maxX = $player->getPosition()->x + 5.5;
                    $minY = $player->getPosition()->y - 5.5;
                    $maxY = $player->getPosition()->y + 5.5;
                    $minZ = $player->getPosition()->z - 5.5;
                    $maxZ = $player->getPosition()->z + 5.5;
                    $entities = $player->getWorld()->getNearbyEntities(new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ), $player);
                    foreach ($entities as $entity){
                        if ($entity instanceof Player){
                            $entitySession = Session::get($entity);
                            $playerSession = Session::get($player);
                            if (! $entitySession->isLoaded() || ! $playerSession->isLoaded()) {
                                continue;
                            }

                            if ($entitySession->getKingdom()?->getId() !== $playerSession->getKingdom()?->getId()) {
                                continue;
                            }

                            if (! Main::getInstance()->getCombatManager()->isTagged($entity)){
                                MessagesUtils::sendTo($player, MessagesIds::CANT_HEAL_UNTAGGED);
                                continue;
                            }

                            if($entity->getHealth() > ($projectile->getPosition() === PotionType::HEALING ? HealConfig::getHealingSplashPotionMinHealth() : HealConfig::getStrongHealingSplashPotionMinHealth())){
                                MessagesUtils::sendTo($player, MessagesIds::CANT_HEAL_HIGH_HEALTH, [
                                    ExtraTags::HEALTH => 10 * 5,
                                ]);
                                continue;
                            }

                            $bonus = $projectile->getPotionType() === PotionType::HEALING ? HealConfig::getHealingLoyaltyBonus() : HealConfig::getStrongHealingLoyaltyBonus();
                            $this->main->getLoyaltyManager()->addLoyalty($player, LoyaltyCause::HEAL_ALLY, $bonus);
                        }
                    }


                    if ($projectile->getPotionType() === PotionType::HEALING){
                        $projectile->setPotionType(PotionType::WATER);
                        $player->setHealth(min($player->getMaxHealth(), $player->getHealth() + HealConfig::getHealingSplashPotionHeal()));
                    } elseif ($projectile->getPotionType() === PotionType::STRONG_HEALING){
                        $projectile->setPotionType(PotionType::WATER);
                        $player->setHealth(min($player->getMaxHealth(), $player->getHealth() + HealConfig::getStrongHealingSplashPotionHeal()));
                    }
                }
            }
        }
    }

    public function onUse(PlayerItemUseEvent $event): void{
        $item = $event->getItem();

        if($item instanceof SplashPotionItem && ($item->getType() === PotionType::STRONG_HEALING || $item->getType() === PotionType::HEALING)){
            $player = $event->getPlayer();
            $items = [];
            $item->onClickAir($player, $player->getDirectionVector(), $items);
            $item = clone $item;
            $item->setCount($item->getCount() - 1);
            $player->getInventory()->setItemInHand($item);
        }
    }

}