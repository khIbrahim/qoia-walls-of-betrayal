<?php

namespace fenomeno\WallsOfBetrayal\Enum;

use pocketmine\utils\TextFormat;

enum AbilityRarity
{

    case COMMUN;
    case LEGENDARY;
    case EPIC;
    case RARE;

    public function getColor(): string
    {
        return match ($this){
            self::COMMUN => TextFormat::GRAY,
            self::LEGENDARY => TextFormat::GOLD,
            self::EPIC => TextFormat::DARK_PURPLE,
            self::RARE => TextFormat::AQUA,
        };
    }

}