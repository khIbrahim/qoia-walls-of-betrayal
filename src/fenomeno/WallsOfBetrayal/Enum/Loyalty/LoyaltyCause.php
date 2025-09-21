<?php

namespace fenomeno\WallsOfBetrayal\Enum\Loyalty;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\item\Item;

enum LoyaltyCause: string
{

    case DEFENSE_SUCCESS  = 'defense';
    case ENEMY_KILL       = 'enemy_kill';
    case ACTIVE_PLAYTIME  = 'playtime';
    case DONATION         = 'donation';
    case HEAL_ALLY        = 'heal_ally';
    case STRUCTURE_REPAIR = 'repair';
    case KINGDOM_VOTE     = 'vote';
    case RESOURCE_SUPPORT = 'resource_support';

    case DEATH            = 'death';
    case COMBAT_LOG       = 'combat_log';
    case TEAM_ATTACK      = 'team_attack';
    case AFK              = 'afk';
    case THEFT            = 'theft';
    case ALERT_IGNORED    = 'alert_ignored';
    case RELIC_LOSS       = 'relic_loss';
    case ENEMY_AID        = 'enemy_aid';

    public function defaultDelta(): int {
        return match($this) {
            self::DEFENSE_SUCCESS  => 5,
            self::ENEMY_KILL       => 2,
            self::ACTIVE_PLAYTIME  => 1,
            self::DONATION         => 2,
            self::HEAL_ALLY        => 3,
            self::STRUCTURE_REPAIR => 4,
            self::KINGDOM_VOTE     => 3,
            self::RESOURCE_SUPPORT => 2,

            self::DEATH         => -2,
            self::COMBAT_LOG    => -5,
            self::TEAM_ATTACK   => -15,
            self::AFK           => -1,
            self::THEFT         => -3,
            self::ALERT_IGNORED => -4,
            self::RELIC_LOSS    => -7,
            self::ENEMY_AID     => -10,
        };
    }

    public function getDisplayName(): string
    {
        return Main::getInstance()->getLoyaltyManager()->getCauseDisplayName($this);
    }

    public function getDescription(): string
    {
        return Main::getInstance()->getLoyaltyManager()->getCauseDescription($this);
    }

    public function getDisplayItem(): Item
    {
        return Main::getInstance()->getLoyaltyManager()->getCauseDisplayItem($this);
    }

    public function isPositive(): bool {
        return $this->defaultDelta() > 0;
    }

}