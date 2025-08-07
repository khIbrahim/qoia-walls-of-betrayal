<?php

namespace fenomeno\WallsOfBetrayal\Enum;

/**
 * Represents the different states of the central wall separating the kingdoms.
 */
enum WallStateEnum: string
{
    // The wall is fully intact. No one can cross.
    case INTACT = 'intact';

    // The wall is starting to destabilize. Visual cracks may appear.
    case CRACKING = 'cracking';

    // The wall has fallen. PvP is now enabled across both territories.
    case BREACHED = 'breached';

    // The wall is being rebuilt or manually restored.
    case REGENERATING = 'regenerating';

    /**
     * Returns a stylized display name for the wall state,
     * fitting the medieval betrayal theme of the game.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::INTACT => "§7[§8Wall: §2Intact§7]",
            self::CRACKING => "§7[§8Wall: §6Cracking§7]",
            self::BREACHED => "§7[§8Wall: §cBreached§7]",
            self::REGENERATING => "§7[§8Wall: §9Rebuilding§7]",
        };
    }
}
