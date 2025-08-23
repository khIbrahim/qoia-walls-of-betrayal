<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class NpcArgument extends StringEnumArgument
{

    public static array $VALUES = [];

    public function parse(string $argument, CommandSender $sender): string
    {
        return self::$VALUES[$argument] ?? $argument;
    }

    public function getTypeName(): string{return "npc";}
    public function getEnumName(): string{return "npc";}
}