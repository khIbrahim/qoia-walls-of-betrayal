<?php
namespace fenomeno\WallsOfBetrayal\DTO;

final readonly class PlayerData
{

    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $kingdom   = null,
        public array   $abilities = [],
        public int     $kills     = 0,
        public int     $deaths    = 0
    ){}

}