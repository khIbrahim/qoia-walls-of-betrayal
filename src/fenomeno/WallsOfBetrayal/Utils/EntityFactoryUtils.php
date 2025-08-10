<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use Closure;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\world\World;
use ReflectionClass;

class EntityFactoryUtils
{

    public static function registerEntity(string $className, string $identifier, ?Closure $creationFunc = null, string $behaviourId = ""): void {
        EntityFactory::getInstance()->register($className, $creationFunc ?? static function (World $world, CompoundTag $nbt) use ($className): Entity {
            return new $className(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, [$identifier]);
        self::updateStaticPacketCache($identifier, $behaviourId);
    }

    private static function updateStaticPacketCache(string $identifier, string $behaviourId): void {
        $instance = StaticPacketCache::getInstance();
        $property = (new ReflectionClass($instance))->getProperty("availableActorIdentifiers");
        /** @var AvailableActorIdentifiersPacket $packet */
        $packet = $property->getValue($instance);
        /** @var CompoundTag $root */
        $root = $packet->identifiers->getRoot();
        ($root->getListTag("idlist") ?? new ListTag())->push(CompoundTag::create()
            ->setString("id", $identifier)
            ->setString("bid", $behaviourId));
        $packet->identifiers = new CacheableNbt($root);
    }

}