<?php

namespace fenomeno\WallsOfBetrayal\Logs;

use JsonSerializable;

class LogRecord implements JsonSerializable
{
    public function __construct(
        public float  $timestamp,
        public int    $level,
        public string $levelName,
        public string $channel,
        public string $message,
        public array  $context = [],
        public array  $extra   = []
    ){}

    public function jsonSerialize(): array
    {
        return [
            'ts'      => $this->timestamp,
            'level'   => $this->levelName,
            'channel' => $this->channel,
            'message' => $this->message,
            'context' => $this->context,
            'extra'   => $this->extra
        ];
    }
}