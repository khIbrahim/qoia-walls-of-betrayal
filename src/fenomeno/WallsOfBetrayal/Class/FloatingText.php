<?php

namespace fenomeno\WallsOfBetrayal\Class;

use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use pocketmine\block\VanillaBlocks;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use pocketmine\world\Position;

class FloatingText
{

    private const TEXT_OFFSET_Y = 1.25;
    private const TEXT_OFFSET_X = 0.5;
    private const TEXT_OFFSET_Z = 0.5;

    private int $runtimeId;

    public function __construct(
        private readonly string   $id,
        private readonly Position $position,
        private string            $text,
    )
    {
        $this->runtimeId = crc32($id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getRuntimeId(): int
    {
        return $this->runtimeId;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function sendTo(Player $player): void
    {
        $pos = $this->getPosition()->add(
            self::TEXT_OFFSET_X,
            self::TEXT_OFFSET_Y,
            self::TEXT_OFFSET_Z
        );

        $entityId = $this->getRuntimeId();

        $player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($entityId));

        $text = $this->replacePlaceholders($this->getText(), $player);
        $metadata = [
            EntityMetadataProperties::FLAGS =>
                new LongMetadataProperty(1 << EntityMetadataFlags::NO_AI),
            EntityMetadataProperties::SCALE =>
                new FloatMetadataProperty(0.01),
            EntityMetadataProperties::BOUNDING_BOX_WIDTH =>
                new FloatMetadataProperty(0.0),
            EntityMetadataProperties::BOUNDING_BOX_HEIGHT =>
                new FloatMetadataProperty(0.0),
            EntityMetadataProperties::NAMETAG =>
                new StringMetadataProperty($text),
            EntityMetadataProperties::VARIANT =>
                new IntMetadataProperty(TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId())),
            EntityMetadataProperties::ALWAYS_SHOW_NAMETAG =>
                new ByteMetadataProperty(1),
            EntityMetadataProperties::MARK_VARIANT =>
                new ByteMetadataProperty(1),
        ];

        $player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
            actorUniqueId: $entityId,
            actorRuntimeId: $entityId,
            type: EntityIds::PLAYER,
            position: $pos,
            motion: null,
            pitch: 0.0,
            yaw: 0.0,
            headYaw: 0.0,
            bodyYaw: 0.0,
            attributes: [],
            metadata: $metadata,
            syncedProperties: new PropertySyncData([], []),
            links: []
        ));
    }

    public function updateFor(Player $player): void
    {
        $text = $this->replacePlaceholders($this->getText(), $player);

        $player->getNetworkSession()->sendDataPacket(SetActorDataPacket::create(
            actorRuntimeId: $this->getRuntimeId(),
            metadata: [
                EntityMetadataProperties::NAMETAG => new StringMetadataProperty($text)
            ],
            syncedProperties: new PropertySyncData([], []),
            tick: 0,
        ));
    }

    private function replacePlaceholders(string $text, Player $player): array|string
    {
        return str_replace([
            ExtraTags::PLAYER,
            ExtraTags::WORLD,
            ExtraTags::PING
        ], [
            $player->getName(),
            $player->getWorld()->getFolderName(),
            $player->getNetworkSession()->getPing()
        ], $text);
    }

    public function hideFor(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($this->getRuntimeId()));
    }

}