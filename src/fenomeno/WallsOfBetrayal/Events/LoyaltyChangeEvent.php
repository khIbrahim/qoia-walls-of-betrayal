<?php

namespace fenomeno\WallsOfBetrayal\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class LoyaltyChangeEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Player $player,
        private readonly int $oldScore,
        private readonly int $newScore,
        private readonly string $reason = ''
    ) {
        $this->player = $player;
    }

    public function getOldScore(): int
    {
        return $this->oldScore;
    }

    public function getNewScore(): int
    {
        return $this->newScore;
    }

    public function getChange(): int
    {
        return $this->newScore - $this->oldScore;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function isIncrease(): bool
    {
        return $this->getChange() > 0;
    }

    public function isDecrease(): bool
    {
        return $this->getChange() < 0;
    }
}