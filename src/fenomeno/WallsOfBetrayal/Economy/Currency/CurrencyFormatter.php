<?php

namespace fenomeno\WallsOfBetrayal\Economy\Currency;

use InvalidArgumentException;

final class CurrencyFormatter
{
    private const COMPACT = "compact";
    private const COMMADOT = "commadot";

    public function __construct(private readonly Currency $currency) {}

    public function format(int $amount): string
    {
        return match ($this->currency->format) {
            CurrencyFormatter::COMPACT => CurrencyFormatter::compact($amount),
            CurrencyFormatter::COMMADOT => CurrencyFormatter::commadot($amount),

            default => throw new InvalidArgumentException("Invalid formatter " . $this->currency->format),
        };
    }

    public  function compact(int $number): string
    {
        $str = match (true) {
            $number >= 1_000_000_000_000_000_000 => round($number / 1_000_000_000_000_000, 2) . "Q",
            $number >= 1_000_000_000_000_000 => round($number / 1_000_000_000_000_000, 2) . "q",
            $number >= 1_000_000_000_000 => round($number / 1_000_000_000_000, 2) . "t",
            $number >= 1_000_000_000 => round($number / 1_000_000_000, 2) . "B",
            $number >= 1_000_000 => round($number / 1_000_000, 2) . "M",
            $number >= 1_000 => round($number / 1_000, 2) . "K",

            default => (string)$number,
        };


        return $this->currency->symbol . $str;
    }

    public function commadot(int $number): string
    {
        $number = (string)$number;
        $length = strlen($number);
        $formatted = "";
        $i = 0;

        while ($i < $length) {
            $formatted .= $number[$i];
            if (($length - $i) % 3 === 1 && $i !== $length - 1) {
                $formatted .= ",";
            }
            $i++;
        }

        return $this->currency->symbol . $formatted;
    }
}