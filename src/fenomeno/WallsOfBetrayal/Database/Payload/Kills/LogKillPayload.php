<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kills;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class LogKillPayload implements PayloadInterface
{

    public function __construct(
        public string $killer,
        public string $victim,
        public string $weapon,
        public string $opponents,
        public int    $killerHealth,
        public bool   $teamKill,
        public bool   $in_kingdom,
    ){}

    public function jsonSerialize(): array
    {
        return [
            'killer'        => $this->killer,
            'victim'        => $this->victim,
            'weapon'        => $this->weapon,
            'opponents'     => $this->opponents,
            'killer_health' => $this->killerHealth,
            'teamkill'      => $this->teamKill,
            'in_kingdom'    => $this->in_kingdom,
        ];
    }
}