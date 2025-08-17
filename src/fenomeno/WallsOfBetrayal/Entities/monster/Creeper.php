<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Creeper extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.6, 1.8);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CREEPER;
    }

    public function getName(): string
    {
        return "Creeper";
    }

    public function getXpDropAmount(): int
    {
        return 6;
    }

    public function getDrops(): array
    {
        return [
            VanillaItems::GUNPOWDER()
        ];
    }

}