<?php

namespace fenomeno\WallsOfBetrayal\Class\Punishment;

class Report extends AbstractPunishment
{

    public function __construct(string $target, string $reason, string $staff, ?int $expiration = null, int $id = 0, ?int $createdAt = null, bool $active = true)
    {
        parent::__construct($target, $reason, $staff, $expiration, $id, $createdAt, $active);
    }

    public function getType(): string
    {
        return AbstractPunishment::TYPE_REPORT;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->getId(),
            'target'     => $this->getTarget(),
            'reason'     => $this->getReason(),
            'staff'      => $this->getStaff(),
            'createdAt'  => $this->createdAt,
            'expiration' => $this->expiration,
            'active'     => $this->active,
            'type'       => $this->getType()
        ];
    }
}