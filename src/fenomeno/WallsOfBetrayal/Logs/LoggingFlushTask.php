<?php

namespace fenomeno\WallsOfBetrayal\Logs;

use pocketmine\scheduler\Task;

class LoggingFlushTask extends Task
{
    public function __construct(private readonly LoggingManager $manager){}

    public function onRun(): void
    {
        $this->manager->flush();
    }
}

