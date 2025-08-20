<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Manager\PunishmentManager;
use pocketmine\scheduler\Task;

class PunishmentTask extends Task
{

    public function __construct(private readonly PunishmentManager $manager){}

    public function onRun(): void
    {
        foreach ($this->manager->getActiveMutes() as $mute) {
            if ($mute->isExpired()) {
                Await::g2c($this->manager->unmutePlayer($mute->getTarget()));
            }
        }
        foreach ($this->manager->getActiveBans() as $ban) {
            if ($ban->isExpired()) {
                $ban->setActive(false);
                Await::g2c($this->manager->unbanPlayer($ban->getTarget()));
            }
        }
    }
}