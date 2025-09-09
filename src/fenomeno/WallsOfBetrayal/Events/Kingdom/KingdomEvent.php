<?php

namespace fenomeno\WallsOfBetrayal\Events\Kingdom;

use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use pocketmine\event\Event;

abstract class KingdomEvent extends Event
{
    protected Kingdom $kingdom;

    public function getKingdom(): Kingdom
    {
        return $this->kingdom;
    }

}