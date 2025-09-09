<?php

namespace fenomeno\WallsOfBetrayal\DTO;

final readonly class SavedPlayerInventories
{

    public function __construct(
        public array $inv,
        public array $armor,
        public array $offhand,
        public string $context
    ){}
}