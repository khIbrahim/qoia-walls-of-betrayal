<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Player;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidPayload;

final readonly class UpdatePlayerStatsPayload extends UuidPayload
{

    public function __construct(
        ?string $uuid,
        public int $kills,
        public int $deaths
    ){
        parent::__construct($uuid);
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid'   => $this->uuid,
            'kills'  => $this->kills,
            'deaths' => $this->deaths
        ];
    }
}