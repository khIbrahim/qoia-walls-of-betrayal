<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum WallStateEnum: string
{

    case INTACT = 'intact';

    case BREACHED = 'breached';

    public function displayName(): string
    {
        return match ($this) {
            self::INTACT => "§7[§8Wall: §2Intact§7]",
            self::BREACHED => "§7[§8Wall: §cBreached§7]",
        };
    }
}
