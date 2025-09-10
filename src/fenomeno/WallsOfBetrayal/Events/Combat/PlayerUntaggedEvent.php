<?php

namespace fenomeno\WallsOfBetrayal\Events\Combat;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerUntaggedEvent extends PlayerEvent
{

    public function __construct(Player $player)
    {
        $this->player = $player;
    }
}