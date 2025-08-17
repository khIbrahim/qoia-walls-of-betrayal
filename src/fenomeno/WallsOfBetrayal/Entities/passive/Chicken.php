<?php
namespace fenomeno\WallsOfBetrayal\Entities\passive;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Chicken extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.8, 0.6);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CHICKEN;
    }

    public function getName(): string
    {
        return "Chicken";
    }

    public function getXpDropAmount() : int{
        return 4;
    }

    public function getDrops() : array{
        return [
            VanillaItems::RAW_CHICKEN()->setCount(mt_rand(1, 2)),
            VanillaItems::FEATHER()->setCount(mt_rand(0, 1)),
        ];
    }

}