<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\scheduler\Task;

class RolesTask extends Task
{

    public function __construct(private readonly Main $main){}

    public function onRun(): void
    {
        foreach ($this->main->getServer()->getOnlinePlayers() as $player) {
            if(! $player->isConnected() || ! Session::get($player)->isLoaded()){
                continue;
            }

            Await::g2c(
                $this->main->getRolesManager()->formatNameTag($player),
                function (string $format) use ($player) {
                    $player->setNameTag($format);
                },
                function (\Throwable $e) use ($player) {
                    $this->main->getLogger()->error("Error formatting name tag: " . $e->getMessage());
                    $player->setNameTag("Loading ...");
                }
            );
        }
    }
}