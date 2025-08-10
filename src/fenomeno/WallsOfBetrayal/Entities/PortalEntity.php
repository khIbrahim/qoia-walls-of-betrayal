<?php

namespace fenomeno\WallsOfBetrayal\Entities;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class PortalEntity extends Living
{

    private static string $portalId;

    public function __construct(Location $location, string $portalId, ?CompoundTag $nbt = null)
    {
        self::$portalId = $portalId;
        var_dump(self::$portalId);

        parent::__construct($location, $nbt);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $kingdom = Main::getInstance()->getKingdomManager()->getKingdomByPortalId(self::$portalId);
        if($kingdom){
            $this->setNameTag("--------" . $kingdom->getDisplayName() . "-§f-------\n\n" .  $kingdom->description . "\n");
        }

        $this->setNameTagAlwaysVisible();
    }
    public static function getNetworkTypeId(): string
    {
        return self::$portalId;
    }

    public function getName(): string
    {
        return 'StellairePortalEntity';
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player) {
                $this->flagForDespawn();
                $damager->sendMessage("TODO");
            }
        } else {
            $source->cancel();
        }
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(5.0, 3, 1.62);
    }
}