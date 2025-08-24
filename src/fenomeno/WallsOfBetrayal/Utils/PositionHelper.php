<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\world\Position;
use RuntimeException;

class PositionHelper {

    public static function load(array $data) : Position
    {
        $worldManager = Server::getInstance()->getWorldManager();
        $folderName = $data["world"] ?? "world";
        $worldManager->loadWorld($folderName);
        $world = $worldManager->getWorldByName($folderName);
        if ($world == null) {
            throw new RuntimeException("World $folderName does not exists");
        }
        if (! $world->isLoaded()){
            $worldManager->loadWorld($folderName);
            return self::load($data);
        }

        if (isset($data['yaw'], $data['pitch'])){
            return new Location((float) $data['x'], (float) $data['y'], (float) $data['z'], $world, (float) $data['yaw'], (float) $data['pitch']);
        }

        return new Position((float) $data["x"], (float) $data["y"], (float) $data["z"], $world);
    }

    public static function toArray(Position $position): array
    {
        $default = [
            'x'     => (float) $position->x,
            'y'     => (float) $position->y,
            'z'     => (float) $position->z,
            'world' => $position->getWorld()->getFolderName()
        ];

        if ($position instanceof Location){
            $default['yaw']   = $position->yaw;
            $default['pitch'] = $position->pitch;
        }

        return $default;
    }

}