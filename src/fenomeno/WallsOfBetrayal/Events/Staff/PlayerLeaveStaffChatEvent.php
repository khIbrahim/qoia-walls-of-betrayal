<?php

namespace fenomeno\WallsOfBetrayal\Events\Staff;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerLeaveStaffChatEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

}