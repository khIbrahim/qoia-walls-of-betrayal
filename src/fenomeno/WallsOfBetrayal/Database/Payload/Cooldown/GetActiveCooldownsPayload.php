<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Cooldown;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

class GetActiveCooldownsPayload implements PayloadInterface
{

    public function jsonSerialize(): array
    {
        return [
            'currentTime' => time()
        ];
    }
}