<?php
namespace fenomeno\WallsOfBetrayal\Entities\neutral;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class PolarBeer extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.4, 1.3);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::POLAR_BEAR;
    }

    public function getName(): string
    {
        return "Polar Bear";
    }

    public function getDrops(): array
    {
        return [VanillaItems::RAW_SALMON(), VanillaItems::RAW_FISH()];
    }
}