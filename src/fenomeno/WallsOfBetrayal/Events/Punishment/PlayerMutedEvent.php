<?php

namespace fenomeno\WallsOfBetrayal\Events\Punishment;

use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;

class PlayerMutedEvent extends SanctionEvent
{

    private AbstractPunishment $punishment;

    public function __construct(AbstractPunishment $punishment)
    {
        parent::__construct(
            $punishment->getTarget(),
            $punishment->getStaff(),
            $punishment->getReason(),
            $punishment->getExpiration()
        );
        $this->punishment = $punishment;
    }

    public function getModerationAction(): string
    {
        return AbstractPunishment::TYPE_MUTE;
    }

    public function getPunishment(): AbstractPunishment
    {
        return $this->punishment;
    }

    public function getPunishmentId(): int
    {
        return $this->punishment->getId();
    }

}