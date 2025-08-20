<?php
namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\config;

use pmmp\thread\ThreadSafe;

final class WebhookLogger extends ThreadSafe {

    public const LEVEL_DEBUG   = 10;
    public const LEVEL_INFO    = 20;
    public const LEVEL_WARNING = 30;
    public const LEVEL_ERROR   = 40;

    private \Logger $pmLogger;

    public function __construct(
        ?\Logger              $pmLogger,
        private readonly int  $level,
        private readonly bool $logRateLimits,
        private readonly bool $logPayloadErrors
    ){
        $this->pmLogger = $pmLogger;
    }

    public static function levelFromString(string $s): int {
        return match (strtoupper(trim($s))) {
            'DEBUG'   => self::LEVEL_DEBUG,
            'WARNING' => self::LEVEL_WARNING,
            'ERROR'   => self::LEVEL_ERROR,
            default   => self::LEVEL_INFO,
        };
    }

    public function shouldLogRateLimits(): bool { return $this->logRateLimits; }
    public function shouldLogPayloadErrors(): bool { return $this->logPayloadErrors; }
    public function isEnabled(int $lvl): bool { return $lvl >= $this->level; }

    public function debug(string $msg): void   { $this->log(self::LEVEL_DEBUG, $msg); }
    public function info(string $msg): void    { $this->log(self::LEVEL_INFO, $msg); }
    public function warning(string $msg): void { $this->log(self::LEVEL_WARNING, $msg); }
    public function error(string $msg): void   { $this->log(self::LEVEL_ERROR, $msg); }

    public function log(int $level, string $msg): void
    {
        if(! $this->isEnabled($level)) return;

        match ($level) {
            self::LEVEL_DEBUG   => $this->pmLogger->debug($msg),
            self::LEVEL_WARNING => $this->pmLogger->warning($msg),
            self::LEVEL_ERROR   => $this->pmLogger->error($msg),
            default             => $this->pmLogger->info($msg),
        };
    }
}