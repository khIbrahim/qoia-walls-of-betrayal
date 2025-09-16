<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\scheduler\Task;

/**
 * Task that runs every 20 ticks (1 second) to handle loyalty-related timing
 */
class LoyaltyTask extends Task
{
    private int $ticks = 0;
    private int $playtimeCounter = 0;  // Counter for active playtime (30 min intervals)
    private int $afkCounter = 0;       // Counter for AFK checks (10 min intervals)

    public function __construct(private readonly Main $main) {}

    public function onRun(): void
    {
        $this->ticks++;

        // Check AFK every 10 minutes (12000 ticks)
        if ($this->ticks % 12000 === 0) {
            $this->main->getLoyaltyManager()->checkAFKPlayers();
            $this->afkCounter++;
        }

        // Award active playtime loyalty every 30 minutes (36000 ticks)
        if ($this->ticks % 36000 === 0) {
            $this->main->getLoyaltyManager()->checkActivePlaytime();
            $this->playtimeCounter++;
        }

        // Reset counter every hour to prevent overflow
        if ($this->ticks >= 72000) { // 1 hour
            $this->ticks = 0;
        }
    }
}