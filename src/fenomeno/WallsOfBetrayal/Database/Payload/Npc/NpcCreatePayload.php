<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Npc;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;
use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\PositionHelper;

readonly class NpcCreatePayload implements PayloadInterface
{

    public function __construct(
        public string $id,
        public string $name,
        public string $command,
        public int    $cooldown,
        public array  $position,
        public float  $yaw,
        public float  $pitch,
        public string $skin,
        public string $skinId,
        public string $cape,
        public string $geometryName,
        public string $geometry
    ){}

    public static function fromNpc(NpcEntity $entity): static
    {
        return new static(
            id: $entity->getNpcId() ?? uniqid("npc"),
            name: $entity->getNameTag(),
            command: $entity->getStoredCommand(),
            cooldown: $entity->getCooldown(),
            position: PositionHelper::toArray($entity->getPosition()),
            yaw: $entity->getLocation()->yaw,
            pitch: $entity->getLocation()->pitch,
            skin: Main::getInstance()->getDatabaseManager()->getBinaryStringParser()->encode(base64_encode($entity->getSkin()->getSkinData())) ?? "",
            skinId: $entity->getSkin()->getSkinId(),
            cape: Main::getInstance()->getDatabaseManager()->getBinaryStringParser()->encode(base64_encode($entity->getSkin()->getCapeData())) ?? "",
            geometryName: $entity->getSkin()->getGeometryName(),
            geometry: Main::getInstance()->getDatabaseManager()->getBinaryStringParser()->encode(base64_encode($entity->getSkin()->getGeometryData())) ?? "",
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'command'       => $this->command,
            'cooldown'      => $this->cooldown,
            'pos'           => json_encode($this->position),
            'yaw'           => $this->yaw,
            'pitch'         => $this->pitch,
            'skin'          => $this->skin,
            'skin_id'       => $this->skinId,
            'cape'          => $this->cape,
            'geometry_name' => $this->geometryName,
            'geometry'      => $this->geometry
        ];
    }
}