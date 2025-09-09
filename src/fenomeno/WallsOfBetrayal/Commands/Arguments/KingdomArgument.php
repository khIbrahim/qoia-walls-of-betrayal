<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\command\CommandSender;

/**
 * Hardcoded kingdom arg, pour la V1
 */
class KingdomArgument extends StringEnumArgument
{

    public static array $VALUES = [
        'thragmar' => 'thragmar',
        'gorvok'   => 'gorvok',
    ];

    public function parse(string $argument, CommandSender $sender): string|Kingdom
    {
        return Main::getInstance()->getKingdomManager()->getKingdomById(strtolower($argument)) ?? $argument;
    }

    public function getTypeName(): string{return "kingdom";}

    public function getEnumName(): string{return "kingdom";}
}