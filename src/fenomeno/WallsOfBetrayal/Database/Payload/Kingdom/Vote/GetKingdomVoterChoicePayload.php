<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote;

use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;

final readonly class GetKingdomVoterChoicePayload extends IdPayload
{

    public function __construct(
        int|string    $id,
        public string $name
    )
    {
        parent::__construct($id);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'name' => $this->name,
        ]);
    }

}