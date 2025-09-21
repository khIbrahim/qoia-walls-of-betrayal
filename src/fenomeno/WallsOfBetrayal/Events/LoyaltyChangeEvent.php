<?php

namespace fenomeno\WallsOfBetrayal\Events;

use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyCause;
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
        private int $newScore,
        private LoyaltyCause $cause
    ){
        $this->player = $player;
    }

    /**
     * @return int
     */
    public function getOldScore(): int
    {
        return $this->oldScore;
    }

    /**
     * @return int
     */
    public function getNewScore(): int
    {
        return $this->newScore;
    }

    /**
     * @param int $newScore
     */
    public function setNewScore(int $newScore): void
    {
        $this->newScore = $newScore;
    }

    public function getCause(): LoyaltyCause
    {
        return $this->cause;
    }

    public function setCause(LoyaltyCause $cause): void
    {
        $this->cause = $cause;
    }

    public function getChange(): int
    {
        return $this->newScore - $this->oldScore;
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