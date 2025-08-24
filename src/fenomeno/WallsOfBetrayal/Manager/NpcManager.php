<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use Closure;
use fenomeno\WallsOfBetrayal\Commands\Arguments\NpcArgument;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Npc\NpcCreatePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Npc\NpcUpdatePayload;
use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\Exceptions\Npc\UnknownNpcException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\Server;
use Throwable;

final class NpcManager
{

    /** @var NpcEntity[] */
    private array $npcs = [];

    public function __construct(private readonly Main $main){}

    public function add(NpcEntity $entity): Generator
    {
        try {
            yield from $this->main->getDatabaseManager()->getNpcRepository()->create(NpcCreatePayload::fromNpc($entity));
        } catch (Throwable){
            yield from $this->main->getDatabaseManager()->getNpcRepository()->update(NpcUpdatePayload::fromNpc($entity));
        }

        $this->npcs[$entity->getNpcId()] = $entity;
        NpcArgument::$VALUES[$entity->getNpcId()] = $entity->getNpcId();

        return $entity;
    }

    /**
     * @throws UnknownNpcException
     */
    public function remove(string $id): Generator
    {
        $npc = $this->getNpcById($id);

        if(! $npc instanceof NpcEntity){
            throw new UnknownNpcException("Unknown npc id : " . $id);
        }

        yield from $this->main->getDatabaseManager()->getNpcRepository()->delete(new IdPayload($id));

        if(! $npc->isFlaggedForDespawn()){
            $npc->flagForDespawn();
        }

        unset($this->npcs[$id]);
        unset(NpcArgument::$VALUES[$id]);

        return $id;
    }

    public function update(NpcEntity $entity, Closure $update): Generator
    {
        $update($entity);

        yield from $this->main->getDatabaseManager()->getNpcRepository()->update(NpcUpdatePayload::fromNpc($entity));

        return $entity;
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

    public function asyncLoadFromDatabase(): Generator
    {
        /** @var array<string,NpcEntity> $npcs */
        $npcs = yield from $this->main->getDatabaseManager()->getNpcRepository()->loadAll();

        if (empty($npcs)) {
            return [0, 0, 0];
        }

        $wm = Server::getInstance()->getWorldManager();

        $present = [];
        foreach ($wm->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof NpcEntity && $entity->getNpcId() !== '') {
                    $present[$entity->getNpcId()] = true;
                }
            }
        }

        $tasks = [];
        $manager = $this->main->getNpcManager();

        foreach ($npcs as $id => $npc) {
            $tasks[] = (function () use ($npc, $id, $present, $manager, $wm): Generator {
                if (isset($present[$id])) {
                    return ['already' => 1, 'worldMissing' => 0, 'loaded' => 0, 'msg' => "$id already spawned"];
                }
                if ($manager->getNpcById($id) !== null) {
                    return ['already' => 1, 'worldMissing' => 0, 'loaded' => 0, 'msg' => "$id already in manager"];
                }

                $world = $npc->getLocation()->getWorld();
                if (! $wm->isWorldLoaded($world->getFolderName())) {
                    return ['already' => 0, 'worldMissing' => 1, 'loaded' => 0, 'msg' => "$id world not loaded"];
                }

                $npc->spawnToAll();
                try {
                    yield from $manager->add($npc);
                } catch (Throwable $e) {
                    $this->main->getLogger()->warning("NPC $id spawned but failed to persist: " . $e->getMessage());
                }

                return ['already' => 0, 'worldMissing' => 0, 'loaded' => 1, 'msg' => "$id spawned"];
            })();
        }

        $results = yield from Await::all($tasks);

        $loaded = $already = $worldMissing = 0;
        foreach ($results as $r) {
            $loaded      += $r['loaded'];
            $already     += $r['already'];
            $worldMissing+= $r['worldMissing'];
        }

        return [$loaded, $already, $worldMissing];
    }

}