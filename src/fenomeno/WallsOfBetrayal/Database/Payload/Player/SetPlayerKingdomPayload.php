<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Player;

use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidUsernamePayload;

final readonly class SetPlayerKingdomPayload extends UuidUsernamePayload
{

    public function __construct(
        ?string        $uuid,
        ?string        $username,
        public ?string $kingdomId = null,
        public array   $abilities = []
    )
    {
        parent::__construct($uuid, $username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'kingdom'   => $this->kingdomId,
            'abilities' => json_encode($this->abilities)
        ]);
    }
}