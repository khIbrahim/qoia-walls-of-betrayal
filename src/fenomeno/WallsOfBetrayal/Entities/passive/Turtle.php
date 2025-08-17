<?php
namespace fenomeno\WallsOfBetrayal\Entities\passive;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Turtle extends SpawnerEntity {

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setMaxHealth(30);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::TURTLE;
    }

    public function getName(): string
    {
        return "Zombie en diamant";
    }

    public function getDrops(): array {
        return [];
    }

    public function getXpDropAmount(): int {
        return 0;
    }

}