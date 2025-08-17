<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Piglin extends SpawnerEntity {

    public function getXpDropAmount(): int
    {
        return 5;
    }

    public function getName(): string{
        return "Piglin";
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::PIGLIN;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.6, 0.6);
    }

    public function getDrops(): array
    {
        return [VanillaItems::GOLDEN_SWORD()];
    }

}