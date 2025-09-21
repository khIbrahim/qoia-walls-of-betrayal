<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\Game\Loyalty\AFK\PlayerActivityTracker;
use pocketmine\scheduler\Task;

class AfkCheckTask extends Task
{

    private int $ticks           = 0;
    private int $playtimeCounter = 0;
    private int $afkCounter      = 0;

    public function __construct(
        private readonly PlayerActivityTracker $tracker,
    ){}

    public function onRun(): void
    {
        $this->ticks++;

        if($this->ticks % $this->tracker->getCheckAFKInterval() === 0){
            $this->afkCounter++;
            $this->tracker->checkAFKPlayers();
        }

        if ($this->ticks % $this->tracker->getCheckPlaytimeInterval() === 0) {
            $this->tracker->checkActivePlaytime();
            $this->playtimeCounter++;
        }

        if ($this->ticks >= 72000) {
            $this->ticks = 0;
        }
    }
}