<?php

namespace fenomeno\WallsOfBetrayal\Game\Phase;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class DayChangeEvent extends Event implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        private readonly int $from,
        private int          $to
    ){}

    public function getFrom(): int
    {
        return $this->from;
    }

    public function setTo(int $to): void
    {
        $this->to = $to;
    }

    public function getTo(): int
    {
        return $this->to;
    }

}