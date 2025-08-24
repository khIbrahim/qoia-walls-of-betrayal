<?php

namespace fenomeno\WallsOfBetrayal\Events;

use pocketmine\entity\Location;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;

class PlayerJoinLobbyEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Player $player,
        private Position|Location $location
    ){
        $this->player = $player;
    }

    public function getLocation(): Position|Location
    {
        return $this->location;
    }

    public function setLocation(Position|Location $location): void
    {
        $this->location = $location;
    }

}