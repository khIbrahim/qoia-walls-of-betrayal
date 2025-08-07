<?php

namespace fenomeno\WallsOfBetrayal\Events;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerJoinWobEvent extends PlayerEvent
{

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

}