<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\Manager\PunishmentManager;
use pocketmine\scheduler\Task;

class PunishmentTask extends Task
{

    public function __construct(private readonly PunishmentManager $manager){}

    /** @throws (is handled with Await::g2c) */
    public function onRun(): void
    {
        $this->manager->getBanManager()->removeExpired();
        $this->manager->getMuteManager()->removeExpired();
    }
}