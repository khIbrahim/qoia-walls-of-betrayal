<?php

namespace fenomeno\WallsOfBetrayal\Events\Kingdom;

use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

final class PlayerEnterKingdomBaseEvent extends KingdomEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Kingdom $kingdom,
        private readonly Player $player
    ){
        $this->kingdom = $kingdom;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

}