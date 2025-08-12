<?php

namespace fenomeno\WallsOfBetrayal\Cache;

class EconomyEntry
{

    public function __construct(
        public string $username,
        public string $uuid,
        public int    $amount,
        public ?int   $position = null
    ){}

}