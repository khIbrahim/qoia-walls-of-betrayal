<?php
namespace fenomeno\WallsOfBetrayal\Class\Punishment;

class Mute extends AbstractPunishment
{
    public function getType(): string
    {
        return self::TYPE_MUTE;
    }

    public function toArray(): array
    {
        return [
            'target' => $this->target,
            'reason' => $this->reason,
            'staff' => $this->staff,
            'expiration' => $this->expiration,
        ];
    }
}