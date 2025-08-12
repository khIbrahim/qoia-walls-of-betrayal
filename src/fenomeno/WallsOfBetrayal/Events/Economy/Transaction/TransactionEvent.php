<?php

namespace fenomeno\WallsOfBetrayal\Events\Economy\Transaction;

use fenomeno\WallsOfBetrayal\Database\Economy\BaseTransaction;
use pocketmine\event\Event;

abstract class TransactionEvent extends Event
{
    public function __construct(public readonly BaseTransaction $transaction) {}
}