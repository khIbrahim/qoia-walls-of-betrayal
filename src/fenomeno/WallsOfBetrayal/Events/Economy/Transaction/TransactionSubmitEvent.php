<?php

namespace fenomeno\WallsOfBetrayal\Events\Economy\Transaction;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

/**
 * This event is called when a transaction is submitted.
 */
final class TransactionSubmitEvent extends TransactionEvent implements Cancellable
{
    use CancellableTrait;
}