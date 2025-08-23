<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\FloatingText;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;

final readonly class UpdateFloatingTextPayload extends IdPayload
{

    public function __construct(
        int|string $id,
        public string $text
    ){
        parent::__construct($id);
    }

    public function jsonSerialize(): array
    {
        return [
            'id'   => $this->id,
            'text' => $this->text
        ];
    }
}