<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Skeleton extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SKELETON;
    }

    public function getName(): string
    {
        return "Skeleton";
    }

    public function getDrops(): array
    {
        return [VanillaItems::BONE()];
    }

    public function getXpDropAmount(): int
    {
        return 0;
    }
}