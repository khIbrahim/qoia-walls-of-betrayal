<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class SeasonArgument extends StringEnumArgument
{

    public function parse(string $argument, CommandSender $sender): mixed
    {
        return self::$VALUES[strtolower($argument)] ?? null;
    }

    public function getTypeName(): string{return 'season';}

    public function getEnumName(): string{return 'season';}
}