<?php

namespace fenomeno\WallsOfBetrayal\Class;

use pocketmine\entity\Location;
use pocketmine\world\Position;

class KingdomData
{

    public function __construct(
        public int $xp = 0,
        public int $balance = 0,
        public int $kills = 0,
        public int $deaths = 0,
        public null|Location|Position $spawn = null
    ){}

}