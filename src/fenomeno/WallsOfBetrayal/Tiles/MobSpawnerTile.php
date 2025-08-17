<?php
namespace fenomeno\WallsOfBetrayal\Tiles;

use fenomeno\WallsOfBetrayal\Blocks\Types\MobSpawnerBlock;
use pocketmine\block\tile\Spawnable;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\player\Player;

class MobSpawnerTile extends Spawnable {

    public const TAG_LEGACY_ENTITY_TYPE_ID = "EntityId"; //TAG_Int
    public const TAG_ENTITY_TYPE_ID = "EntityIdentifier"; //TAG_String
    public const TAG_SPAWN_DELAY = "Delay"; //TAG_Short
    public const TAG_MIN_SPAWN_DELAY = "MinSpawnDelay"; //TAG_Short
    public const TAG_MAX_SPAWN_DELAY = "MaxSpawnDelay"; //TAG_Short
    public const TAG_SPAWN_PER_ATTEMPT = "SpawnCount"; //TAG_Short
    public const TAG_MAX_NEARBY_ENTITIES = "MaxNearbyEntities"; //TAG_Short
    public const TAG_REQUIRED_PLAYER_RANGE = "RequiredPlayerRange"; //TAG_Short
    public const TAG_SPAWN_RANGE = "SpawnRange"; //TAG_Short
    public const TAG_NIVEAU = "Niveau";

    public const DEFAULT_MIN_SPAWN_DELAY = 40; //ticks
    public const DEFAULT_MAX_SPAWN_DELAY = 60;
    protected int $tick = 20;

    public const DEFAULT_MAX_NEARBY_ENTITIES = 6;
    public const DEFAULT_SPAWN_RANGE = 3; //blocks
    public const DEFAULT_REQUIRED_PLAYER_RANGE = 16;
    public const DEFAULT_NIVEAU = 1;

    /** TODO: replace this with a cached entity or something of that nature */
    private string $entityTypeId = ":";
    protected int $legacyEntityTypeId = 0;

    private int $spawnDelay = self::DEFAULT_MIN_SPAWN_DELAY;
    private int $minSpawnDelay = self::DEFAULT_MIN_SPAWN_DELAY;
    private int $maxSpawnDelay = self::DEFAULT_MAX_SPAWN_DELAY;
    private int $spawnPerAttempt = 1;
    private int $maxNearbyEntities = self::DEFAULT_MAX_NEARBY_ENTITIES;
    private int $spawnRange = self::DEFAULT_SPAWN_RANGE;
    private int $requiredPlayerRange = self::DEFAULT_REQUIRED_PLAYER_RANGE;
    protected int $niveau = self::DEFAULT_NIVEAU;

    public function getLegacyEntityId() : Int{
        return $this->legacyEntityTypeId;
    }

    public function setLegacyEntityId(Int $id) : Void{
        $this->entityTypeId = LegacyEntityIdToStringIdMap::getInstance()->legacyToString($this->legacyEntityTypeId = $id) ?? ':';
        $block = $this->getBlock();
        if($block instanceof MobSpawnerBlock){
            $block->setLegacyEntityId($id);
        }
    }

    public function getEntityId() : String{
        return $this->entityTypeId;
    }

    public function setEntityId(String $id) : Void{
        $this->legacyEntityTypeId = array_search(
            $this->entityTypeId = $id, LegacyEntityIdToStringIdMap::getInstance()->getLegacyToStringMap()
        );
        $block = $this->getBlock();
        if($block instanceof MobSpawnerBlock) {
            $block->setLegacyEntityId($this->legacyEntityTypeId);
        }
    }

    public function getTick(): int
    {
        return $this->tick;
    }

    public function setTick(int $tick): void{
        $this->tick = $tick;
    }

    public function decreaseTick(): void{
        $this->tick--;
    }

    public function getSpawnDelay() : int {
        return $this->spawnDelay;
    }

    public function setSpawnDelay(int $spawnDelay): void
    {
        $this->spawnDelay = $spawnDelay;
    }

    public function decreaseSpawnDelay(): void{
        $this->spawnDelay--;
    }

    public function getMinSpawnDelay(): int
    {
        return $this->minSpawnDelay;
    }

    public function getMaxSpawnDelay(): int
    {
        return $this->maxSpawnDelay;
    }

    public function getSpawnRange(): int
    {
        return $this->spawnRange;
    }

    public function getRequiredPlayerRange(): int
    {
        return $this->requiredPlayerRange;
    }

    public function canGenerate() : bool {
        return $this->entityTypeId !== ":" && $this->position->getWorld()->getNearestEntity($this->position, $this->getRequiredPlayerRange(), Player::class);
    }

    public function readSaveData(CompoundTag $nbt) : void{
        $legacyIdTag = $nbt->getTag(self::TAG_LEGACY_ENTITY_TYPE_ID);
        if($legacyIdTag instanceof IntTag){
            $this->setLegacyEntityId($legacyIdTag->getValue());
        }else{
            $this->setEntityId($nbt->getString(self::TAG_ENTITY_TYPE_ID, ":"));
        }

        $this->niveau = $nbt->getShort(self::TAG_NIVEAU, self::DEFAULT_NIVEAU);
        $this->spawnDelay = $nbt->getShort(self::TAG_SPAWN_DELAY, self::DEFAULT_MIN_SPAWN_DELAY);
        $this->minSpawnDelay = $nbt->getShort(self::TAG_MIN_SPAWN_DELAY, self::DEFAULT_MIN_SPAWN_DELAY);
        $this->maxSpawnDelay = $nbt->getShort(self::TAG_MAX_SPAWN_DELAY, self::DEFAULT_MAX_SPAWN_DELAY);
        $this->spawnPerAttempt = $nbt->getShort(self::TAG_SPAWN_PER_ATTEMPT, 1);
        $this->maxNearbyEntities = $nbt->getShort(self::TAG_MAX_NEARBY_ENTITIES, self::DEFAULT_MAX_NEARBY_ENTITIES);
        $this->requiredPlayerRange = $nbt->getShort(self::TAG_REQUIRED_PLAYER_RANGE, self::DEFAULT_REQUIRED_PLAYER_RANGE);
        $this->spawnRange = $nbt->getShort(self::TAG_SPAWN_RANGE, self::DEFAULT_SPAWN_RANGE);
    }

    protected function writeSaveData(CompoundTag $nbt) : void{
        $nbt->setString(self::TAG_ENTITY_TYPE_ID, $this->entityTypeId);
        $nbt->setShort(self::TAG_NIVEAU, $this->niveau);

        $nbt->setShort(self::TAG_SPAWN_DELAY, $this->spawnDelay);
        $nbt->setShort(self::TAG_MIN_SPAWN_DELAY, $this->minSpawnDelay);
        $nbt->setShort(self::TAG_MAX_SPAWN_DELAY, $this->maxSpawnDelay);
        $nbt->setShort(self::TAG_SPAWN_PER_ATTEMPT, $this->spawnPerAttempt);
        $nbt->setShort(self::TAG_MAX_NEARBY_ENTITIES, $this->maxNearbyEntities);
        $nbt->setShort(self::TAG_REQUIRED_PLAYER_RANGE, $this->requiredPlayerRange);
        $nbt->setShort(self::TAG_SPAWN_RANGE, $this->spawnRange);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
        $nbt->setString(self::TAG_ENTITY_TYPE_ID, $this->entityTypeId);
    }

}