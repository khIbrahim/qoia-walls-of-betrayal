<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class KingdomDataFilterArgument extends StringEnumArgument
{

    public const KILLS = 'kills';
    public const DEATHS = 'deaths';
    public const BALANCE = 'balance';
    public const XP = 'xp';

    public static array $VALUES = [
        self::KILLS => self::KILLS,
        self::DEATHS => self::DEATHS,
        self::BALANCE => self::BALANCE,
        self::XP => self::XP,
    ];

    public function parse(string $argument, CommandSender $sender): mixed
    {
        return self::$VALUES[$argument] ?? null;
    }

    public function getTypeName(): string
    {
        return 'kingdom_top_filter';
    }

    public function getEnumName(): string
    {
        return 'kingdom_top_filter';
    }
}