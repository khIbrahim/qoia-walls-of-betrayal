<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Slime extends SpawnerEntity {

    public function getName(): string{
        return "Slime";
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SLIME;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6);
    }

    public function getDrops() : array{
        return [
            VanillaItems::SLIMEBALL()->setCount(mt_rand(1, 2)),
        ];
    }

    public function getXpDropAmount() : int{
        return 7;
    }

}