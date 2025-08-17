<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

final class KitArgument extends StringEnumArgument
{

    /** @var array<string,Kit>  key = role id */
    public static array $VALUES = [];

    public static function register(string $id, Kit $kit): void {
        self::$VALUES[strtolower($id)] = $kit;
    }

    public function parse(string $argument, CommandSender $sender): string|Kit {
        return $this->getValue($argument);
    }

    public static function reset(): void {
        self::$VALUES = [];
    }

    public function getValue(string $string): string|Kit {
        return self::$VALUES[strtolower($string)] ?? $string;
    }

    public function getEnumValues(): array {
        return array_keys(self::$VALUES);
    }

    public function getTypeName(): string { return "kit"; }
    public function getEnumName(): string { return "kit"; }
}