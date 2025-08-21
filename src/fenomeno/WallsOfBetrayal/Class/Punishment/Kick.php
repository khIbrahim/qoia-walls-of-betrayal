<?php

namespace fenomeno\WallsOfBetrayal\Class\Punishment;

class Kick extends AbstractPunishment
{

    public function getType(): string
    {
        return AbstractPunishment::TYPE_KICK;
    }

    public function toArray(): array
    {
        return [
            'target'     => $this->target,
            'reason'     => $this->reason,
            'staff'      => $this->staff,
            'expiration' => $this->expiration,
        ];
    }
}