<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\NpcRepositoryInterface\NpcRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Npc\NpcCreatePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Npc\NpcUpdatePayload;
use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\PositionHelper;
use Generator;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use Throwable;

class NpcRepository implements NpcRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_NPC, [], function (){
            $this->main->getLogger()->info("§aTable `npc` has been successfully init");
        });
    }

    public function create(NpcCreatePayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::CREATE_NPC, $payload->jsonSerialize());
    }

    public function update(NpcUpdatePayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_NPC, $payload->jsonSerialize());
    }

    public function delete(IdPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::DELETE_NPC, $payload->jsonSerialize());
    }

    public function loadAll(): Generator
    {
        return Await::promise(function ($resolve, $reject){
            Await::f2c(function () use ($resolve, $reject) {
                try {
                    $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_ALL_NPC);
                    if(empty($rows)){
                        $resolve([]);
                        return;
                    }

                    $npcs = [];
                    foreach ($rows as $i => $row){
                        if (empty($row)) continue;
                        try {
                            if(! isset($row['id'], $row['pos'], $row['yaw'], $row['pitch'], $row['skin_id'], $row['cooldown'], $row['command'])){
                                $this->main->getLogger()->error("Unable to parse npc $i, data is missing");
                                continue;
                            }

                            $id = (string) $row['id'];
                            $pos = PositionHelper::load(json_decode($row['pos'], true));
                            $loc = Location::fromObject($pos, $pos->getWorld());

                            $skin = new Skin(
                                skinId: $row["skin_id"] ?? "Standard_Custom",
                                skinData: base64_decode($this->main->getDatabaseManager()->getBinaryStringParser()->decode($row["skin"] ?? ""), true) ?: "",
                                capeData: base64_decode($this->main->getDatabaseManager()->getBinaryStringParser()->decode($row["cape"] ?? ""), true) ?: "",
                                geometryName: (string) $row['geometry_name'],
                                geometryData: base64_decode($this->main->getDatabaseManager()->getBinaryStringParser()->decode($row["geometry_data"] ?? ""), true) ?: ""
                            );

                            $npc = NpcEntity::make(
                                location: $loc,
                                skin: $skin,
                                id: $id,
                                command: (string) ($row["command"] ?? ""),
                                name: (string) ($row["name"] ?? "Wob NPC"),
                                cooldown: (int) $row['cooldown']
                            );

                            $npcs[$npc->getNpcId()] = $npc;
                        } catch (Throwable $e){
                            $this->main->getLogger()->error("Failed to parse npc $i: " . $e->getMessage());
                        }
                    }

                    $resolve($npcs);
                } catch (Throwable $e){
                    $reject($e);
                }
            });
        });
    }

}