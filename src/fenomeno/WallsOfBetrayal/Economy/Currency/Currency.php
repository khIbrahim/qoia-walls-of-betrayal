<?php

namespace fenomeno\WallsOfBetrayal\Economy\Currency;

final class Currency
{
    public readonly CurrencyFormatter $formatter;

    public function __construct(
        public readonly string $name,
        public readonly string $code ,
        public readonly string $symbol,
        public readonly string $format,

        public readonly int    $defaultAmount,
        public readonly int    $defaultDecimals,

        public readonly bool   $decimals,
    ) {
        $this->formatter = new CurrencyFormatter($this);
    }
}