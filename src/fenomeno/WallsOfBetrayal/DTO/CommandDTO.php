<?php
namespace fenomeno\WallsOfBetrayal\DTO;

namespace fenomeno\WallsOfBetrayal\DTO;

final readonly class CommandDTO
{

    public function __construct(
        public string $name,
        public string $description,
        public string $usage,
        public array  $aliases,
        public array  $metadata = []
    ){}

}