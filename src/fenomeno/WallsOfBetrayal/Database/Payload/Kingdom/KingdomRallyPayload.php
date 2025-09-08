<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom;

use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;

final readonly class KingdomRallyPayload extends IdPayload
{

    public function __construct(
        int|string   $id,
        public array $rallyPoint
    )
    {
        parent::__construct($id);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'rally_point' => json_encode($this->rallyPoint)
        ]);
    }

}