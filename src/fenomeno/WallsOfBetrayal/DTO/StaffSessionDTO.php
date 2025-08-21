<?php

namespace fenomeno\WallsOfBetrayal\DTO;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\player\GameMode;

final class StaffSessionDTO implements \JsonSerializable
{

    private const INVENTORY = 'StaffInventory';
    private const OFF_HAND_INVENTORY = 'StaffOffHandInventory';
    private const ARMOR_INVENTORY = 'StaffArmorInventory';

    public function __construct(
        public array    $inventory,
        public array    $offhandInventory,
        public array    $armorInventory,
        public int      $xp,
        public float    $xpProgress,
        public bool     $allowFlight,
        public GameMode $gameMode,
        public ?string  $staff = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            "inventory"        => Main::getInstance()->getDatabaseManager()->writeItems($this->inventory, self::INVENTORY),
            "offhandInventory" => Main::getInstance()->getDatabaseManager()->writeItems($this->offhandInventory, self::OFF_HAND_INVENTORY),
            "armorInventory"   => Main::getInstance()->getDatabaseManager()->writeItems($this->offhandInventory, self::ARMOR_INVENTORY),
            "xp"               => $this->xp,
            "xpProgress"       => $this->xpProgress,
            "allowFlight"      => $this->allowFlight,
            "gameMode"         => $this->gameMode->name
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            inventory: Main::getInstance()->getDatabaseManager()->readItems($data["inventory"], self::INVENTORY),
            offhandInventory: Main::getInstance()->getDatabaseManager()->readItems($data["offhandInventory"], self::OFF_HAND_INVENTORY),
            armorInventory: Main::getInstance()->getDatabaseManager()->readItems($data["armorInventory"], self::ARMOR_INVENTORY),
            xp: $data["xp"] ?? 0,
            xpProgress: $data["xpProgress"] ?? 0.0,
            allowFlight: $data["allowFlight"] ?? false,
            gameMode: GameMode::fromString($data["gameMode"] ?? "SURVIVAL"),
            staff: $data["staff"] ?? null
        );
    }
}