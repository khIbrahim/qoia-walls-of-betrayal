<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Seasons;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;

final readonly class SaveSeasonPayload implements PayloadInterface
{

    // j'en ai marre
    public function __construct(
        public SeasonDTO $season
    ){}

    public function jsonSerialize(): array
    {
        return $this->season->toArray(true);
    }
}