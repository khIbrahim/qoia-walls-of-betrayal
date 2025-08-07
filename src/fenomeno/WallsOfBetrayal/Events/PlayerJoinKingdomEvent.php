<?php

namespace fenomeno\WallsOfBetrayal\Events;

use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

class PlayerJoinKingdomEvent extends Event implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        private readonly Player $player,
        private Kingdom $kingdom
    ){}

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getKingdom(): Kingdom
    {
        return $this->kingdom;
    }

    public function setKingdom(Kingdom $kingdom): void
    {
        $this->kingdom = $kingdom;
    }

}