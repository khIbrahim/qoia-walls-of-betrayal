<?php
namespace fenomeno\WallsOfBetrayal\Entities\neutral;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Spider extends SpawnerEntity {

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SPIDER;
    }

    public function getName(): string
    {
        return "Spider";
    }

    public function getXpDropAmount(): int{
        return 0;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.9, 1.4);
    }

    public function getDrops(): array
    {
        return [VanillaItems::SPIDER_EYE()];
    }
}