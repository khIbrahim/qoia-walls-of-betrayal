<?php
namespace fenomeno\WallsOfBetrayal\Entities\passive;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Cow extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.3, 0.9);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::COW;
    }

    public function getName(): string
    {
        return "Cow";
    }

    public function getDrops() : array{
        return [
            VanillaItems::RAW_BEEF()->setCount(mt_rand(1, 2)),
            VanillaItems::LEATHER()->setCount(mt_rand(0, 1)),
        ];
    }

    public function getXpDropAmount() : int{
        return 4;
    }

}