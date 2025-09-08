<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum KingdomVoteType: string
{

    case Kick = 'kick';

    case Ban = 'ban';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::Kick => 'Banishment from the Kingdom',
            self::Ban => 'Eternal Exile'
        };
    }
}
