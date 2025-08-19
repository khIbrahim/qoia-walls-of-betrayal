<?php

namespace fenomeno\WallsOfBetrayal\Class;

class KingdomData
{

    public function __construct(
        public int $xp = 0,
        public int $balance = 0,
        public int $kills = 0,
        public int $deaths = 0
    ){}

}