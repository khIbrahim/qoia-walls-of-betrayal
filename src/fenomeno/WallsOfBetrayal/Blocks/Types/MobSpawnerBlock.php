<?php

namespace fenomeno\WallsOfBetrayal\Blocks\Types;

use fenomeno\WallsOfBetrayal\Entities\EntityInfo;
use fenomeno\WallsOfBetrayal\Entities\EntityManager;
use fenomeno\WallsOfBetrayal\Entities\SpawnerEntity;
use fenomeno\WallsOfBetrayal\Tiles\MobSpawnerTile;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockTypeIds as Ids;
use pocketmine\block\BlockTypeInfo as Info;
use pocketmine\block\MonsterSpawner;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\entity\Location;
use pocketmine\item\ToolTier;
use pocketmine\world\particle\MobSpawnParticle;

class MobSpawnerBlock extends MonsterSpawner
{

    protected string $entityTypeId = ':';
    protected int $legacyEntityId = 0;
    protected ?EntityInfo $entityInfo = null;

    public function isAffectedBySilkTouch(): bool
    {
        return true;
    }

    public function __construct()
    {
        parent::__construct(new BID(Ids::MONSTER_SPAWNER, MobSpawnerTile::class), "Monster Spawner", new Info(BreakInfo::pickaxe(5.0, ToolTier::WOOD())));
    }

    public function setLegacyEntityId(int $id): self
    {
        $this->entityTypeId = LegacyEntityIdToStringIdMap::getInstance()->legacyToString($this->legacyEntityId = $id) ?? ':';
        $this->entityInfo = EntityManager::getInstance()->getEntityInfo($id);
        return $this;
    }

    public function readStateFromWorld(): Block
    {
        parent::readStateFromWorld();

        $tile = $this->position->getWorld()->getTile($this->position);

        if ($tile instanceof MobSpawnerTile and $tile->getEntityId() !== ':') {
            $this->entityTypeId = $tile->getEntityId();
            $this->legacyEntityId = $tile->getLegacyEntityId();
            $this->entityInfo = EntityManager::getInstance()->getEntityInfo($tile->getLegacyEntityId());
        }

        return $this;
    }

    public function writeStateToWorld(): void
    {
        parent::writeStateToWorld();
        $tile = $this->position->getWorld()->getTile($this->position);
        assert($tile instanceof MobSpawnerTile);
        if ($tile->getEntityId() == ':') {
            $tile->setLegacyEntityId($this->legacyEntityId);
            $this->entityInfo = EntityManager::getInstance()->getEntityInfo($this->legacyEntityId);
        }
    }

    public function onScheduledUpdate(): void
    {
        $tile = $this->position->getWorld()->getTile($this->position);
        if ($tile === null) {
            return;
        }
        if ($tile->isClosed() || !$tile instanceof MobSpawnerTile) {
            return;
        }
        if ($tile->getTick() > 0) {
            $tile->decreaseTick();
        }
        if ($tile->canGenerate() && $tile->getTick() <= 0) {
            $tile->setTick(20);
            if ($tile->getSpawnDelay() < 0) {
                $tile->setSpawnDelay(mt_rand($tile->getMinSpawnDelay(), $tile->getMaxSpawnDelay()));
                if ($this->entityInfo !== null) {
                    $class = $this->entityInfo->getClass();
                    for ($i = 0; $i < $tile->getSpawnRange(); $i++) {
                        $x = ((mt_rand(-10, 10) / 10) * $tile->getSpawnRange()) + 0.5;
                        $z = ((mt_rand(-10, 10) / 10) * $tile->getSpawnRange()) + 0.5;
                        $pos = $tile->getPosition();
                        $pos = new Location($pos->x + $x, $pos->y + mt_rand(1, 3), $pos->z + $z, $pos->getWorld(), 0, 0);
                        $entity = new $class($pos);
                        assert($entity instanceof SpawnerEntity);
                        $entity->spawnToAll();
                        $pos->world->addParticle($pos, new MobSpawnParticle((int)$entity->getSize()->getWidth(), (int)$entity->getSize()->getHeight()));
                    }
                }
            } else $tile->decreaseSpawnDelay();
        }
        $this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 1);
    }

    public function getEntityInfo(): ?EntityInfo
    {
        return $this->entityInfo;
    }
}