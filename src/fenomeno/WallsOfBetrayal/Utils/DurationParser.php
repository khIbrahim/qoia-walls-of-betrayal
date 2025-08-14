<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use InvalidArgumentException;

class DurationParser
{
    public static function fromString(string $argument): int
    {
        $pattern = '/(\d+)([smhd])/i';
        preg_match_all($pattern, $argument, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            throw new InvalidArgumentException("Invalid duration format. Use '1s', '2m', '3h', or '4d'.");
        }

        $totalSeconds = 0;

        foreach ($matches as $match) {
            $value = (int)$match[1];
            $unit = strtolower($match[2]);

            $seconds = match ($unit) {
                's' => $value,
                'm' => $value * 60,
                'h' => $value * 3600,
                'd' => $value * 86400,
                default => throw new InvalidArgumentException("Time unit '$unit' is not recognized."),
            };

            $totalSeconds += $seconds;
        }

        return $totalSeconds;
    }

    public static function getReadableDuration(?int $timestamp): string {
        $seconds = $timestamp - time();

        if ($seconds <= 0 || $timestamp === null) return "permanently";

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        $parts = [];

        if($days > 0) $parts[] = "$days day" . ($days > 1 ? "s" : "");
        if($hours > 0) $parts[] = "$hours hour" . ($hours > 1 ? "s" : "");
        if($minutes > 0) $parts[] = "$minutes minute" . ($minutes > 1 ? "s" : "");
        if($seconds > 0) $parts[] = "$seconds second" . ($seconds > 1 ? "s" : "");
        if (empty($parts)) {
            return "less than a second";
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);
        return implode(', ', $parts) . ' et ' . $last;
    }


}