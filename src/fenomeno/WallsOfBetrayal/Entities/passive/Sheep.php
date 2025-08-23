<?php
namespace fenomeno\WallsOfBetrayal\Entities\passive;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Sheep extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.3, 0.9);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SHEEP;
    }

    public function getName(): string
    {
        return "Sheep";
    }

    public function getDrops(): array
    {
        return [VanillaBlocks::WOOL()->asItem(), VanillaItems::RAW_MUTTON()];
    }

    public function getXpDropAmount(): int
    {
        return 1;
    }

}