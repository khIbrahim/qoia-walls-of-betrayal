<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class BorderArgument extends StringEnumArgument
{

    public const MIN = 'min';
    public const MAX = 'max';

    public static array $VALUES = [
        self::MIN => self::MIN,
        self::MAX => self::MAX
    ];

    public function parse(string $argument, CommandSender $sender): mixed
    {
        return self::$VALUES[$argument] ?? $argument;
    }

    public function getTypeName(): string{return "border";}

    public function getEnumName(): string{return "border";}
}