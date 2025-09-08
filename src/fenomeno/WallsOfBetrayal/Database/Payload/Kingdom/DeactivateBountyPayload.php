<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom;

use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;

readonly class DeactivateBountyPayload extends IdPayload
{

    public function __construct(
        int|string     $id,
        public ?string $takenBy
    )
    {
        parent::__construct($id);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'takenBy' => $this->takenBy
        ]);
    }

}