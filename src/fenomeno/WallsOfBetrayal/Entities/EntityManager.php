<?php

namespace fenomeno\WallsOfBetrayal\Entities;

use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use Symfony\Component\Filesystem\Path;
use Throwable;
use const pocketmine\BEDROCK_DATA_PATH;

class EntityManager {
    use SingletonTrait;

    private const DEFAULT_NAMETAG = "§l§d{COUNT}x §f{NAME}";

    private array $entities = [];
    /** @var EntityInfo[] */
    private array $entityInfoMap = [];

    private bool $oneShot = false;
    private string $nametag;
    private array $mobs = [];

    public function startup(Main $main) : void {
        $main->saveResource('entities.yml', true);
        $config        = new Config(Path::join($main->getDataFolder(), "entities.yml"), Config::YAML);
        $this->oneShot = (bool) $config->getNested("parameters.one_shot");
        $this->nametag = $config->get("parameters.nametag", self::DEFAULT_NAMETAG);
        $this->mobs    = $config->get("mobs", []);

        /** @var array<string, int> $entityIdMap */
        $entityIdMap = json_decode(file_get_contents(BEDROCK_DATA_PATH . "/entity_id_map.json"), true);
        foreach(["passive", "neutral", "monster"] as $type){
            Utils::callDirectory("Entities/$type", function (string $namespace)use($main, $type, $entityIdMap): void{
                try {
                    /** @var Living $namespace */
                    $saveNames = [ucfirst(str_replace("_", " ", str_replace("minecraft:", "", $namespace::getNetworkTypeId()))), $namespace::getNetworkTypeId()];
                    $this->registerEntity($namespace, $saveNames);

                    $entityId = array_pop($saveNames);
                    $legacyId = $entityIdMap[$entityId] ?? null;
                    $name = ucfirst(str_replace("_", " ", str_replace("minecraft:", "", $entityId)));
                    $info = new EntityInfo($namespace, $entityId, $name, $legacyId);
                    $this->entityInfoMap[$legacyId] = $info;
                } catch (Throwable $e) {
                    $main->getLogger()->error("Failed to register entity $namespace: " . $e->getMessage());
                }
            });
        }

        $entitiesNames = array_map(fn(EntityInfo $info) => $info->getName(), $this->entityInfoMap);
        $main->getLogger()->info("§aENTITIES - Registered §6(" . count($this->entities) . ") §aentities: §6(" . implode(", ", $entitiesNames) . "§6)");

        $this->registerTypes();
    }

    public function getEntitiesInfo(): array
    {
        return $this->entityInfoMap;
    }

    private function registerEntity(string $namespace, array $saveNames = []) : void {
        /** @var SpawnerEntity $namespace */
        if ($namespace::isEnabled()){
            EntityFactory::getInstance()->register($namespace, function(World $world, CompoundTag $nbt)use($namespace): Entity{
                return new $namespace(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            }, $saveNames);
            $this->entities[array_pop($saveNames)] = $namespace;
        }
    }

    public function getEntityInfo(int|string $entity): ?EntityInfo{
        return $this->entityInfoMap[$entity] ?? null;
    }

    public function getEntityInfoByName(string $name) : ?EntityInfo
    {
        foreach ($this->entityInfoMap as $entity){
            if (strtolower(str_replace(" ", "_", $entity->getName())) === strtolower($name)){
                return $entity;
            }
        }
        return null;
    }

    public function isOneShot(): bool
    {
        return $this->oneShot;
    }

    public function getNametag(): string
    {
        return $this->nametag;
    }

    public function isEntityEnabled(string $entity): bool
    {
        return isset($this->mobs[$entity]) && $this->mobs[$entity] === true;
    }

    private function registerTypes(): void
    {
        EntityFactory::getInstance()->register(NpcEntity::class, function (World $world, CompoundTag $nbt): NpcEntity {
            return new NpcEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ['WobNPC']);
    }

}