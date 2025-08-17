<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Witch extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::WITCH;
    }

    public function getName(): string
    {
        return "Witch";
    }

    public function getDrops(): array
    {
        $drops = [];
        switch (mt_rand(1, 7)){
            case 1:
                $drops[] = VanillaItems::GLOWSTONE_DUST();
                break;
            case 2:
                $drops[] = VanillaItems::SUGAR();
                break;
            case 3:
                $drops[] = VanillaItems::REDSTONE_DUST();
                break;
            case 4:
                $drops[] = VanillaItems::SPIDER_EYE();
                break;
            case 5:
                $drops[] = VanillaItems::GLASS_BOTTLE();
                break;
            case 6:
                $drops[] = VanillaItems::GUNPOWDER();
                break;
            case 7:
                $drops[] = VanillaItems::STICK();
                break;
        }
        return $drops;
    }

    public function getXpDropAmount(): int
    {
        return 12;
    }

}