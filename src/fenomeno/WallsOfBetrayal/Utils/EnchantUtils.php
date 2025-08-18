<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\VanillaEnchantments;

class EnchantUtils
{

    public static function getEnchantmentName(Enchantment $enchantment): string
    {
        return match ($enchantment->getName()->getText()){
            VanillaEnchantments::PROTECTION()->getName()->getText() => "Protection",
            VanillaEnchantments::FIRE_PROTECTION()->getName()->getText() => "Fire Protection",
            VanillaEnchantments::FEATHER_FALLING()->getName()->getText() => "Feather Falling",
            VanillaEnchantments::BLAST_PROTECTION()->getName()->getText() => "Blast Protection",
            VanillaEnchantments::PROJECTILE_PROTECTION()->getName()->getText() => "Projectile Protection",
            VanillaEnchantments::RESPIRATION()->getName()->getText() => "Respiration",
            VanillaEnchantments::AQUA_AFFINITY()->getName()->getText() => "Aqua Affinity",
            VanillaEnchantments::THORNS()->getName()->getText() => "Thorns",
            VanillaEnchantments::FROST_WALKER()->getName()->getText() => "Frost Walker",
            VanillaEnchantments::SHARPNESS()->getName()->getText() => "Sharpness",
            VanillaEnchantments::KNOCKBACK()->getName()->getText() => "Knockback",
            VanillaEnchantments::FIRE_ASPECT()->getName()->getText() => "Fire Aspect",
            VanillaEnchantments::EFFICIENCY()->getName()->getText() => "Efficiency",
            VanillaEnchantments::SILK_TOUCH()->getName()->getText() => "Silk Touch",
            VanillaEnchantments::UNBREAKING()->getName()->getText() => "Unbreaking",
            VanillaEnchantments::FORTUNE()->getName()->getText() => "Fortune",
            VanillaEnchantments::POWER()->getName()->getText() => "Power",
            VanillaEnchantments::PUNCH()->getName()->getText() => "Punch",
            VanillaEnchantments::FLAME()->getName()->getText() => "Flame",
            VanillaEnchantments::INFINITY()->getName()->getText() => "Infinity",
            VanillaEnchantments::SWIFT_SNEAK()->getName()->getText() => "Swift Sneak",
            default => $enchantment->getName()->getText(),
        };
    }

    public static function formatLevel(int $level): string
    {
        return match ($level){
            1 => "§r",
            2 => "§r",
            3 => "§r",
            4 => "§r",
            5 => "§r",
            default => (string) $level,
        };
    }

    public static function getEnchantmentColor(Enchantment $enchantment): string
    {
        return match ($enchantment->getName()->getText()) {
            VanillaEnchantments::PROTECTION()->getName()->getText() => "§f",
            VanillaEnchantments::FIRE_PROTECTION()->getName()->getText() => "§c",
            VanillaEnchantments::FEATHER_FALLING()->getName()->getText() => "§f",
            VanillaEnchantments::BLAST_PROTECTION()->getName()->getText() => "§2",
            VanillaEnchantments::PROJECTILE_PROTECTION()->getName()->getText() => "§e",
            VanillaEnchantments::RESPIRATION()->getName()->getText() => "§b",
            VanillaEnchantments::AQUA_AFFINITY()->getName()->getText() => "§b",
            VanillaEnchantments::THORNS()->getName()->getText() => "§b",
            VanillaEnchantments::FROST_WALKER()->getName()->getText() => "§b",
            VanillaEnchantments::SHARPNESS()->getName()->getText() => "§4",
            VanillaEnchantments::KNOCKBACK()->getName()->getText() => "§b",
            VanillaEnchantments::FIRE_ASPECT()->getName()->getText() => "§c",
            VanillaEnchantments::EFFICIENCY()->getName()->getText() => "§e",
            VanillaEnchantments::SILK_TOUCH()->getName()->getText() => "§d",
            VanillaEnchantments::UNBREAKING()->getName()->getText() => "§b",
            VanillaEnchantments::FORTUNE()->getName()->getText() => "§a",
            VanillaEnchantments::POWER()->getName()->getText() => "§c",
            VanillaEnchantments::PUNCH()->getName()->getText() => "§b",
            VanillaEnchantments::FLAME()->getName()->getText() => "§c",
            VanillaEnchantments::INFINITY()->getName()->getText() => "§2",
            VanillaEnchantments::SWIFT_SNEAK()->getName()->getText() => "§b",
            default => "§r",
        };
    }

    public static function getEnchantmentEmoji(Enchantment $enchantment): string
    {
        return match ($enchantment->getName()->getText()) {
            VanillaEnchantments::PROTECTION()->getName()->getText() => "",
            VanillaEnchantments::FIRE_PROTECTION()->getName()->getText() => "",
            VanillaEnchantments::FEATHER_FALLING()->getName()->getText() => "",
            VanillaEnchantments::BLAST_PROTECTION()->getName()->getText() => "",
            VanillaEnchantments::PROJECTILE_PROTECTION()->getName()->getText() => "",
            VanillaEnchantments::RESPIRATION()->getName()->getText() => "",
            VanillaEnchantments::AQUA_AFFINITY()->getName()->getText() => "",
            VanillaEnchantments::THORNS()->getName()->getText() => "",
            VanillaEnchantments::FROST_WALKER()->getName()->getText() => "",
            VanillaEnchantments::SHARPNESS()->getName()->getText() => "",
            VanillaEnchantments::KNOCKBACK()->getName()->getText() => "",
            VanillaEnchantments::FIRE_ASPECT()->getName()->getText() => "",
            VanillaEnchantments::EFFICIENCY()->getName()->getText() => "",
            VanillaEnchantments::SILK_TOUCH()->getName()->getText() => "",
            VanillaEnchantments::UNBREAKING()->getName()->getText() => "",
            VanillaEnchantments::FORTUNE()->getName()->getText() => "",
            VanillaEnchantments::POWER()->getName()->getText() => "",
            VanillaEnchantments::PUNCH()->getName()->getText() => "",
            VanillaEnchantments::FLAME()->getName()->getText() => "",
            VanillaEnchantments::INFINITY()->getName()->getText() => "",
            VanillaEnchantments::SWIFT_SNEAK()->getName()->getText() => "",
            default => "",
        };
    }
}