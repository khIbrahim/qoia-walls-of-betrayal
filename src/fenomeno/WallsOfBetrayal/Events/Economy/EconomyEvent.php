<?php

namespace fenomeno\WallsOfBetrayal\Events\Economy;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

abstract class EconomyEvent extends Event implements Cancellable
{
    use CancellableTrait;

}