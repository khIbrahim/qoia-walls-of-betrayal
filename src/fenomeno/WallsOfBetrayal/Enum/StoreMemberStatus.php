<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum StoreMemberStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case ON_BREAK = 'on_break';
    case TERMINATED = 'terminated';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
            self::SUSPENDED => 'Suspendu',
            self::ON_BREAK => 'En congé',
            self::TERMINATED => 'Licencié'
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => '§a',     // Green
            self::INACTIVE => '§7',   // Gray
            self::SUSPENDED => '§c',  // Red
            self::ON_BREAK => '§e',   // Yellow
            self::TERMINATED => '§4'  // Dark Red
        };
    }

    public function canWork(): bool
    {
        return $this === self::ACTIVE;
    }
}