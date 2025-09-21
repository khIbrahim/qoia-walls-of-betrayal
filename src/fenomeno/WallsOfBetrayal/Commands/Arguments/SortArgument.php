<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class SortArgument extends StringEnumArgument
{

    public static array $VALUES = [
        'asc'  => 'asc',
        'desc' => 'desc'
    ];

    public function parse(string $argument, CommandSender $sender): mixed
    {
        return self::$VALUES[strtolower($argument)] ?? null;
    }

    public function getTypeName(): string
    {
        return 'sort_type';
    }

    public function getEnumName(): string
    {
        return 'sort_type';
    }
}