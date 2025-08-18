<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class SellArgument extends StringEnumArgument
{

    public const HAND = 'hand';
    public const ALL = 'all';

    public static array $VALUES = [
        self::HAND => self::HAND,
        self::ALL  => self::ALL,
    ];

    public function parse(string $argument, CommandSender $sender): string
    {
        return $this->getValue($argument);
    }

    public function getTypeName(): string
    {
        return "sell";
    }

    public function getEnumName(): string
    {
        return "sell";
    }
}