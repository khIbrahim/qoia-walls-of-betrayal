<?php

namespace fenomeno\WallsOfBetrayal\DTO;

final readonly class CooldownEntry
{

    public function __construct(
        public string $identifier,
        public string $type,
        public int    $expiryTime
    ){}
}