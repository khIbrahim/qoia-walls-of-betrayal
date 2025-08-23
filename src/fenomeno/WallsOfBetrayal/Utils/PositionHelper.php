<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use pocketmine\Server;
use pocketmine\world\Position;

class PositionHelper {

    public static function load(array $data) : Position
    {
        $worldManager = Server::getInstance()->getWorldManager();
        $folderName = $data["world"] ?? "world";
        $worldManager->loadWorld($folderName);
        $world = $worldManager->getWorldByName($folderName);
        if ($world == null) {
            throw new \RuntimeException("Le monde $folderName n'existe pas");
        }
        if (! $world->isLoaded()){
            $worldManager->loadWorld($folderName);
            return self::load($data);
        }

        return new Position($data["x"], $data["y"], $data["z"], $world);
    }

    public static function positionToArray(Position $position): array
    {
        return [
            'x'     => (float) $position->x,
            'y'     => (float) $position->y,
            'z'     => (float) $position->z,
            'world' => $position->getWorld()->getFolderName()
        ];
    }

}