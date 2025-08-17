<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Blaze extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.5);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::BLAZE;
    }

    public function getName(): string
    {
        return "Blaze";
    }

    public function getDrops(): array
    {
        return [VanillaItems::BLAZE_ROD()->setCount(mt_rand(1, 2))];
    }

    public function getXpDropAmount(): int
    {
        return 0;
    }

}