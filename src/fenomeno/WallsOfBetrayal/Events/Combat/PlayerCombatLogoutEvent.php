<?php

namespace fenomeno\WallsOfBetrayal\Events\Combat;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerCombatLogoutEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Player $player,
        private bool $kill
    ){
        $this->player = $player;
    }

    public function isKill(): bool
    {
        return $this->kill;
    }

    public function setKill(bool $kill): void
    {
        $this->kill = $kill;
    }

}