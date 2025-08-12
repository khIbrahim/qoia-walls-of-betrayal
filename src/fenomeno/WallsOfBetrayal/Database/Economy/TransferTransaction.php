<?php

namespace fenomeno\WallsOfBetrayal\Database\Economy;

final class TransferTransaction extends BaseTransaction
{
    public function __construct(
        public readonly array $source,
        public readonly array $target,

        int $amount,
        int $decimals,
    ) {
        parent::__construct($amount, $decimals);
    }
}