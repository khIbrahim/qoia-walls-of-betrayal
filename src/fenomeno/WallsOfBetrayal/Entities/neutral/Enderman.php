<?php
namespace fenomeno\WallsOfBetrayal\Entities\neutral;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Enderman extends SpawnerEntity {

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);
        $this->setMaxHealth(40);
    }

    public function getXpDropAmount(): int
    {
        return 5;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2.9, 0.6);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ENDERMAN;
    }

    public function getName(): string
    {
        return "Enderman";
    }

    public function getDrops(): array
    {
        return [VanillaItems::ENDER_PEARL()];
    }

}