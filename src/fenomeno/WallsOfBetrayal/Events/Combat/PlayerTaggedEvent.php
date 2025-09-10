<?php

namespace fenomeno\WallsOfBetrayal\Events\Combat;

use pocketmine\event\Event;
use pocketmine\player\Player;

class PlayerTaggedEvent extends Event
{

    public function __construct(
        private readonly Player  $player,
        private readonly ?Player $opponent,
        private int              $combatTime
    ){}

    public function getCombatTime(): int
    {
        return $this->combatTime;
    }

    public function setCombatTime(int $combatTime): void
    {
        $this->combatTime = $combatTime;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getOpponent(): ?Player
    {
        return $this->opponent;
    }
}