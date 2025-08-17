<?php
namespace fenomeno\WallsOfBetrayal\Entities\neutral;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Golem extends SpawnerEntity {

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);
        $this->setMaxHealth(100);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2.9, 1.4);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::IRON_GOLEM;
    }

    public function getName(): string{
        return "Iron Golem";
    }

    public function getXpDropAmount(): int
    {
        return 8;
    }

    public function getDrops(): array
    {
        return [VanillaItems::IRON_INGOT()->setCount(mt_rand(1, 2))];
    }

}