<?php
namespace fenomeno\WallsOfBetrayal\Entities\monster;

use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Zombie extends SpawnerEntity {

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setMaxHealth(20);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ZOMBIE;
    }

    public function getName(): string
    {
        return "Zombie";
    }

    public function getDrops(): array {
        $random = mt_rand(0, 100);
        if ($random <= 96) {
            return [VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))];
        } else {
            return [];
        }
    }

    public function getXpDropAmount(): int {
        return 0;
    }
}