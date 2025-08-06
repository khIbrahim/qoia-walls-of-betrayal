<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\type\InvMenuTypeIds;
use fenomeno\WallsOfBetrayal\Main;
use InvalidArgumentException;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\StringToItemParser;
use Throwable;

class Utils
{

    public static function getInvMenuSize(int $size): string
    {
        if ($size >= 0 && $size <= 9) {
            return InvMenuTypeIds::TYPE_HOPPER;
        } elseif ($size > 9 && $size <= 27) {
            return InvMenuTypeIds::TYPE_CHEST;
        } elseif ($size > 27 && $size <= 54) {
            return InvMenuTypeIds::TYPE_DOUBLE_CHEST;
        } else {
            throw new InvalidArgumentException("Invalid inventory size invalide: $size");
        }
    }

    public static function loadItems(array $itemsData, array $extraTags = []): array
    {
        $items = [];
        foreach ($itemsData as $slot => $itemData) {
            try {
                $item = StringToItemParser::getInstance()->parse($itemData['item'] ?? 'paper');
                if (isset($itemData['display-name'])){
                    $item->setCustomName((string) str_replace(array_keys($extraTags), $extraTags, $itemData['display-name']));
                }
                if (isset($itemData['description'])){
                    if (is_string($itemData['description'])){
                        $description = str_replace(array_keys($extraTags), $extraTags, $itemData['description']);
                        $item->setLore(explode("\n", $description));
                    } elseif(is_array($itemData['description'])) {
                        $item->setLore(array_map(fn($desc) => str_replace(array_keys($extraTags), $extraTags, $desc), $itemData['description']));
                    } else {
                        $item->setLore((array) $itemData['description']);
                    }
                }
                if (isset($itemData['enchantments'])){
                    foreach ($itemData['enchantments'] as $enchantmentName => $level) {
                        $enchant = StringToEnchantmentParser::getInstance()->parse($enchantmentName);
                        $enchantmentInstance = new EnchantmentInstance($enchant, (int) $level);
                        $item->addEnchantment($enchantmentInstance);
                    }
                }

                $items[$slot] = $item;
            } catch (Throwable $e){
                Main::getInstance()->getLogger()->error("An error occurred while parsing item with slot: $slot: " . $e->getMessage());
            }
        }
        return $items;
    }

    public static function loadInventory(array $config): ?InventoryDTO
    {
        if(empty($config)){
            return null;
        }

        $name          = $config['name'] ?? 'Default Inventory';
        $size          = (int) ($config['size'] ?? 27);
        $type          = Utils::getInvMenuSize($size);
        $items         = Utils::loadItems($config['contents'] ?? []);
        $targetIndexes = (array) ($config['targetIndexes'] ?? []);

        return new InventoryDTO(
            name: $name,
            size: $size,
            type: $type,
            items: $items,
            targetIndexes: $targetIndexes
        );
    }

}