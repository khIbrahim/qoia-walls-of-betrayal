<?php
namespace fenomeno\WallsOfBetrayal\DTO;

final readonly class PlayerData
{

    public function __construct(
        public ?string $kingdom   = null,
        public array   $abilities = [],
    ){}

}