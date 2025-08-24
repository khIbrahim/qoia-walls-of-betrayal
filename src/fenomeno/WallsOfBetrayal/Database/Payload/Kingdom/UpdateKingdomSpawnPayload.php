<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom;

use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;

final readonly class UpdateKingdomSpawnPayload extends IdPayload
{

    public function __construct(
        int|string $id,
        public array $spawn
    ){
        parent::__construct($id);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'spawn' => json_encode($this->spawn)
        ]);
    }

}