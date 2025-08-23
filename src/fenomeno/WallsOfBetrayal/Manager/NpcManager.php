<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Commands\Arguments\NpcArgument;
use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\Exceptions\Npc\UnknownNpcException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\utils\Config;
use pocketmine\world\World;
use Throwable;

final class NpcManager
{

    /** @var NpcEntity[] */
    private array $npcs = [];

    private Config $config;

    public function __construct(private readonly Main $main)
    {
        $this->config = new Config($this->main->getDataFolder() . 'npc.json', Config::JSON);
    }

    public function add(NpcEntity $entity): void
    {
        $this->npcs[$entity->getNpcId()] = $entity;

        NpcArgument::$VALUES[$entity->getNpcId()] = $entity->getNpcId();

        $this->saveAll();
    }

    public function remove(string $id): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($id) {
            $npc = $this->getNpcById($id);

            if(! $npc instanceof NpcEntity){
                $reject(new UnknownNpcException("Unknown npc id : " . $id));
                return;
            }

            if(! $npc->isFlaggedForDespawn()){
                $npc->flagForDespawn();
            }

            unset($this->npcs[$id]);
            unset(NpcArgument::$VALUES[$id]);
            $resolve($id);
        });
    }

    public function getNpcById(string $id): ?NpcEntity
    {
        return $this->npcs[$id] ?? null;
    }

    /** @return NpcEntity[] */
    public function getAll(): array
    {
        return $this->npcs;
    }

    /** @throws */
    public function saveAll(): void
    {
        $data = [];
        foreach ($this->npcs as $npc){
            $data[$npc->getNpcId()] = $npc->toArray();
        }

        $this->config->setAll($data);
        $this->config->save();
    }

    /** @throws */
    public function spawnAllFromConfigByWorld(string|world $targetWorld, bool $load = false): void {
        $data = $this->config->getAll();

        foreach($data as $id => $row){
            try {
                if(! isset($row['world'], $row['x'], $row['y'], $row['z'], $row['yaw'], $row['pitch'], $row['skin'], $row['skin_id'], $row['cape'], $row['geometry_name'], $row['geometry_data'])){
                    $this->main->getLogger()->error("Unable to parse npc $id, data is missing");
                    continue;
                }

                $worldName       = (string) ($row["world"] ?? 'world');
                $targetWorldName = $targetWorld instanceof World ? $targetWorld->getFolderName() : $targetWorld;

                if (strtolower($targetWorldName) != strtolower($worldName)){
                    continue;
                }

                if ($load){
                    $this->main->getServer()->getWorldManager()->loadWorld($worldName);
                }
                $world = $this->main->getServer()->getWorldManager()->getWorldByName($worldName);
                if(! $world instanceof World){
                    $this->main->getLogger()->warning("Unable to parse $worldName world to load npc " . $id);
                    continue;
                }

                $loc = new Location(
                    x: (float) $row["x"],
                    y: (float) $row["y"],
                    z: (float) $row["z"],
                    world: $world,
                    yaw: (float) $row["yaw"],
                    pitch: (float) $row["pitch"]
                );
                $skin = new Skin(
                    skinId: $row["skin_id"] ?? "Standard_Custom",
                    skinData: base64_decode($row["skin"] ?? "", true) ?: "",
                    capeData: base64_decode($row["cape"] ?? "", true) ?: "",
                    geometryName: (string) $row['geometry_name'],
                    geometryData: base64_decode($row['geometry_data'])
                );

                $npc = NpcEntity::make(
                    location: $loc,
                    skin: $skin,
                    id: $id,
                    command: (string) ($row["command"] ?? ""),
                    name: (string) ($row["name"] ?? "Wob NPC")
                );
                $npc->spawnToAll();
                $this->add($npc);
            } catch (Throwable $e) {
                $this->main->getLogger()->error("Failed to parse npc $id: " . $e->getMessage());
            }
        }
    }

}