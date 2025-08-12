<?php

namespace fenomeno\WallsOfBetrayal\Events\Economy;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

final class CacheInvalidateEvent extends Event implements Cancellable
{
    use CancellableTrait;

    public function __construct(public readonly array $online, public readonly array $top) {}
}