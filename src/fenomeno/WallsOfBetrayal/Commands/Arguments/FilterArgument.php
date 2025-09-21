<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class FilterArgument extends StringEnumArgument
{

    public static array $VALUES = [
        'all'      => 'all',
        'positive' => 'positive',
        'negative' => 'negative'
    ];

    public function parse(string $argument, CommandSender $sender): mixed
    {
        return self::$VALUES[strtolower($argument)] ?? null;
    }

    public function getTypeName(): string
    {
        return 'filter_type';
    }

    public function getEnumName(): string
    {
        return 'filter_type';
    }
}