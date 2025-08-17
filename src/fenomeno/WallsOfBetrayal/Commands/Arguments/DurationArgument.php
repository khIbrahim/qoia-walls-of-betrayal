<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use pocketmine\command\CommandSender;

final class DurationArgument extends RawStringArgument
{

    public function canParse(string $testString, CommandSender $sender): bool
    {
        try {
            DurationParser::fromString($testString);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    public function parse(string $argument, CommandSender $sender): string
    {
        $durationInSeconds = DurationParser::fromString($argument);
        return (string)(time() + $durationInSeconds);
    }

    public function getTypeName(): string
    {
        return "duration";
    }

}