<?php
declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\Class\Roles\Role;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

final class RoleArgument extends StringEnumArgument {
    /** @var array<string,Role>  key = role id */
    public static array $VALUES = [];

    public static function register(string $id, Role $role): void {
        self::$VALUES[strtolower($id)] = $role;
    }

    public static function reset(): void {
        self::$VALUES = [];
    }

    public function getValue(string $string): ?Role {
        return self::$VALUES[strtolower($string)] ?? null;
    }

    public function getEnumValues(): array {
        return array_keys(self::$VALUES);
    }

    public function getTypeName(): string { return "role"; }
    public function getEnumName(): string { return "role"; }

    public function parse(string $argument, CommandSender $sender): ?Role {
        return $this->getValue($argument);
    }
}