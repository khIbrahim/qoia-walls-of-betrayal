<?php

namespace fenomeno\WallsOfBetrayal\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerNickChangeEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    private string $newNick;

    public function __construct(Player $player, string $newNickname)
    {
        $this->player = $player;
        $this->newNick = $newNickname;
    }

    public function getNewNick(): string
    {
        return $this->newNick;
    }
}