<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Guardian extends SpawnerEntity {

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.85, 0.85);
    }

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);
        $this->setMaxHealth(30);
    }

    public function getName(): string{
        return "Guardian";
    }

    public function getXpDropAmount(): int{
        return 10;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::GUARDIAN;
    }

}