<?php

namespace fenomeno\WallsOfBetrayal\Database\Economy;

abstract class BaseTransaction
{
    public function __construct(public readonly int $amount, public readonly int $decimals) {}
}