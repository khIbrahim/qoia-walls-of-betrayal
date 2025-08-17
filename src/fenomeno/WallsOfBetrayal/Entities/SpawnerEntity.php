<?php
namespace fenomeno\WallsOfBetrayal\Entities;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

abstract class SpawnerEntity extends Living {

    public const COUNT_TAG = "Count";

    private int $count = 1;

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        if ($nbt->getTag(self::COUNT_TAG) !== null) {
            $this->count = $nbt->getInt(self::COUNT_TAG, 1);
        } else {
            $this->count = 1;
        }
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setInt(self::COUNT_TAG, $this->count);

        return $nbt;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function addCount(int $count = 1) : void
    {
        $this->count += $count;
        $this->updateNameTag();
    }

    public function reduceCount(int $count = 1) : void
    {
        $this->count -= $count;
        $this->updateNametag();
    }

    public function spawnToAll(): void
    {
        parent::spawnToAll();
        $minX = (int) floor($this->location->x - 16 );
        $maxX = (int) ceil($this->location->x + 16);
        $minY = (int) floor(World::Y_MIN);
        $maxY = (int) ceil(World::Y_MAX);
        $minZ = (int) floor($this->location->z - 16);
        $maxZ = (int) ceil($this->location->z + 16);
        $list = array_filter($this->getWorld()->getNearbyEntities(new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ), $this), function (Entity $entity){
            return $entity instanceof SpawnerEntity;
        });
        if(count($list) === 0){
            return;
        }
        foreach($list as $entity){
            assert($entity instanceof SpawnerEntity);
            if ($entity->getName() === $this->getName()){
                if($entity->getCount() > 1){
                    $this->flagForDespawn();
                    $entity->addCount();
                } else {
                    $entity->flagForDespawn();
                    $this->addCount();
                }
            }
        }
    }

    public function kill(): void
    {
        if ($this->count > 1){
            $ev = new EntityDeathEvent($this, static::getDrops(), static::getXpDropAmount());
            $ev->call();
            $this->reduceCount();
            foreach($ev->getDrops() as $drop){
                $this->getWorld()->dropItem($this->location, $drop);
            }
            $this->getWorld()->dropExperience($this->location, $ev->getXpDropAmount());
        } else {
            parent::kill();
        }
    }

    public function attack(EntityDamageEvent $source): void
    {
        if (EntityManager::getInstance()->isOneShot()){
            self::kill();
            return;
        }
        parent::attack($source);
    }

    protected function updateNametag() : void
    {
        $this->setNameTagAlwaysVisible();
        $this->setNameTag(str_replace(["{COUNT}", "{NAME}"], [$this->count, $this->getName()], EntityManager::getInstance()->getNametag()));
    }

    public static function getNetworkTypeId(): string
    {
        return static::getNetworkTypeId();
    }

    public static function isEnabled(): bool
    {
        return EntityManager::getInstance()->isEntityEnabled(str_replace("minecraft:", "", self::getNetworkTypeId()));
    }

}