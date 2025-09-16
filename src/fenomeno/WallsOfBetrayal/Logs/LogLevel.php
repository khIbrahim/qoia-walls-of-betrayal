<?php

namespace fenomeno\WallsOfBetrayal\Logs;

final class LogLevel
{
    public const DEBUG   = 100;
    public const INFO    = 200;
    public const NOTICE  = 250;
    public const WARNING = 300;
    public const ERROR   = 400;
    public const CRITICAL= 500;

    private const MAP = [
        self::DEBUG    => 'DEBUG',
        self::INFO     => 'INFO',
        self::NOTICE   => 'NOTICE',
        self::WARNING  => 'WARNING',
        self::ERROR    => 'ERROR',
        self::CRITICAL => 'CRITICAL',
    ];

    public static function toString(int $level): string
    {
        return self::MAP[$level] ?? (string) $level;
    }
}