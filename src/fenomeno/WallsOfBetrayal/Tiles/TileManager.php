<?php

namespace fenomeno\WallsOfBetrayal\Tiles;

use fenomeno\WallsOfBetrayal\Blocks\Tiles\TotemHealTile;
use pocketmine\block\tile\TileFactory;
use pocketmine\utils\SingletonTrait;

class TileManager
{

    use SingletonTrait;

    public function startup() : void {
        TileFactory::getInstance()->register(MobSpawnerTile::class, ['MobSpawner', 'minecraft:mob_spawner']);
        TileFactory::getInstance()->register(TotemHealTile::class, ['TotemHeal', 'wob:totem_heal']);
    }

}