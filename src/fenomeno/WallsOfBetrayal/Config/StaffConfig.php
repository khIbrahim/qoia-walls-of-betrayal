<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;
use Throwable;

final class StaffConfig
{
    /** @var Item[] */
    private static array $staffInventory = [];
    private static bool $banOnDisconnectWhileFrozen = false;
    private static array $events = [];

    public const STAFF_MOD_TAG = 'StaffModItem';
    public const STAFF_MOD_TYPE_TAG = 'StaffModItemType';
    public const STAFF_MOD_COMMAND_TAG = 'StaffModItemCommand';

    public static function init(Main $main): void
    {
        try {
            $main->saveResource('staff.yml');
            $config = new Config($main->getDataFolder() . "staff.yml", Config::YAML);
            foreach ((array) $config->getNested('staffmod.inventory', []) as $slot => $itemData){
                try {
                    $item = StringToItemParser::getInstance()->parse($itemData["item"]);
                    if($item === null) {
                        $main->getLogger()->warning("§cItem invalide pour le staffmod slot $slot");
                        continue;
                    }
                    $item->setCustomName("§r§f" . $itemData["customName"] ?? "§r§fOutil staff");
                    $item->getNamedTag()->setByte(self::STAFF_MOD_TAG, 1);
                    $item->getNamedTag()->setString(self::STAFF_MOD_COMMAND_TAG, $itemData["command"] ?? "");
                    $item->getNamedTag()->setString(self::STAFF_MOD_TYPE_TAG, $itemData["type"] ?? "");
                    self::$staffInventory[$slot] = $item;
                } catch (Throwable $e){
                    $main->getLogger()->error("Failed to parse staff item $slot: " . $e->getMessage());
                }
            }

            self::$banOnDisconnectWhileFrozen = $config->getNested("staffmod.banOnDisconnectWhileFrozen", false);
            self::$events = $config->getNested("staffmod.events", [
                "chat" => true,
                "interact" => true,
                "place" => true,
                "break" => true,
                "pickup" => true,
                "drop" => true,
                "damage" => true,
            ]);

        } catch (Throwable $e) {
            $main->getLogger()->error("§cErrors parsing staff config: " . $e->getMessage());
        }
    }

    public static function isChatAllowed(): bool{return self::$events["chat"] ?? true;}
    public static function isPlaceAllowed(): bool{return self::$events["place"] ?? true;}
    public static function isBreakAllowed(): bool{return self::$events["break"] ?? true;}
    public static function isPickupAllowed(): bool{return self::$events["pickup"] ?? true;}
    public static function isDropAllowed(): bool{return self::$events["drop"] ?? true;}
    public static function isDamageAllowed(): bool{return self::$events["damage"] ?? true;}

    public static function getStaffInventory(): array
    {
        return self::$staffInventory;
    }

    public static function isBanOnDisconnectWhileFrozen(): bool
    {
        return self::$banOnDisconnectWhileFrozen;
    }

    public static function getConfigPath(): string
    {
        return Main::getInstance()->getDataFolder() . "staffs.json";
    }
}