<?php

namespace fenomeno\WallsOfBetrayal\Events;

use fenomeno\WallsOfBetrayal\Enum\PhaseEnum;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class PhaseChangeEvent extends Event implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        private readonly PhaseEnum $from,
        private PhaseEnum          $to,
    ){}

    public function getFrom(): PhaseEnum
    {
        return $this->from;
    }

    public function getTo(): PhaseEnum
    {
        return $this->to;
    }

    public function setTo(PhaseEnum $to): void
    {
        $this->to = $to;
    }

}