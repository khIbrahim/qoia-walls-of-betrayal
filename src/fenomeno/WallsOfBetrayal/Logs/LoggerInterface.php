<?php

namespace fenomeno\WallsOfBetrayal\Logs;

interface LoggerInterface
{
    public function log(int $level, string $channel, string $message, array $context = [], array $extra = []): void;

    public function debug(string $channel, string $message, array $context = [], array $extra = []): void;
    public function info(string $channel, string $message, array $context = [], array $extra = []): void;
    public function notice(string $channel, string $message, array $context = [], array $extra = []): void;
    public function warning(string $channel, string $message, array $context = [], array $extra = []): void;
    public function error(string $channel, string $message, array $context = [], array $extra = []): void;
    public function critical(string $channel, string $message, array $context = [], array $extra = []): void;
}