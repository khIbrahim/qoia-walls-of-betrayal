<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\config;

use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\model\WebhookTemplate;
use InvalidArgumentException;
use Logger;
use Throwable;

final class WebhookConfiguration
{
    private const DEFAULT_USER_AGENT       = "libWebhook";
    private const DEFAULT_CONNECT_TIMEOUT  = 400;
    private const DEFAULT_REQUEST_TIMEOUT  = 2000;
    private const DEFAULT_VERIFY_PEER      = false;
    private const DEFAULT_VERIFY_HOST      = false;
    private const DEFAULT_MAX_QUEUE_SIZE   = 2048;
    private const DEFAULT_THREAD_COUNT     = 1;

    private const DEFAULT_LOG_RATELIMITS   = true;
    private const DEFAULT_LOG_PAYLOAD_ERRS = true;

    public readonly string $userAgent;
    public readonly int    $connectTimeoutMs;
    public readonly int    $requestTimeoutMs;
    public readonly bool   $verifyPeer;
    public readonly bool   $verifyHost;
    public readonly int    $maxQueueSize;
    public readonly int    $threadCount;

    public readonly int  $logLevel;
    public readonly bool $logRateLimits;
    public readonly bool $logPayloadErrors;

    /** @var array<string,WebhookTemplate> */
    private array $templates = [];

    public function __construct(array $root)
    {
        $global = $root['global'] ?? [];
        $log    = $global['logging'] ?? [];

        $this->userAgent        = (string)($global['user-agent'] ?? self::DEFAULT_USER_AGENT);

        $timeouts               = (array)($global['timeouts'] ?? []);
        $this->connectTimeoutMs = (int)($timeouts['connect-ms'] ?? self::DEFAULT_CONNECT_TIMEOUT);
        $this->requestTimeoutMs = (int)($timeouts['request-ms'] ?? self::DEFAULT_REQUEST_TIMEOUT);

        $ssl                    = (array)($global['ssl'] ?? []);
        $this->verifyPeer       = (bool)($ssl['verify-peer'] ?? self::DEFAULT_VERIFY_PEER);
        $this->verifyHost       = (bool)($ssl['verify-host'] ?? self::DEFAULT_VERIFY_HOST);

        $queue                  = (array)($global['queue'] ?? []);
        $this->maxQueueSize     = (int)($queue['max-size'] ?? self::DEFAULT_MAX_QUEUE_SIZE);

        $workers                = (array)($global['workers'] ?? []);
        $this->threadCount      = max(1, (int)($workers['threads'] ?? self::DEFAULT_THREAD_COUNT));

        $this->logLevel         = WebhookLogger::levelFromString((string)($log['level'] ?? 'INFO'));
        $this->logRateLimits    = (bool)($log['log-ratelimits'] ?? self::DEFAULT_LOG_RATELIMITS);
        $this->logPayloadErrors = (bool)($log['log-payload-errors'] ?? self::DEFAULT_LOG_PAYLOAD_ERRS);

        $this->loadTemplates((array) ($root['templates'] ?? []));

        $this->validate();
    }

    private function validate(): void
    {
        if ($this->connectTimeoutMs <= 0) {
            throw new InvalidArgumentException("webhook.global.timeouts.connect-ms must be > 0");
        }

        if ($this->requestTimeoutMs <= 0) {
            throw new InvalidArgumentException("webhook.global.timeouts.request-ms must be > 0");
        }

        if ($this->maxQueueSize <= 0) {
            throw new InvalidArgumentException("webhook.global.queue.max-size must be > 0");
        }

        if ($this->threadCount < 1) {
            throw new InvalidArgumentException("webhook.global.workers.threads must be >= 1");
        }

        if (! empty($this->templates) && ! isset($this->templates['default'])) {
            throw new InvalidArgumentException("webhook.templates.default is required");
        }
    }

    public function getTemplates(): array {
        return $this->templates;
    }


    public function createLogger(?Logger $pmLogger = null): WebhookLogger
    {
        return new WebhookLogger(
            $pmLogger,
            $this->logLevel,
            $this->logRateLimits,
            $this->logPayloadErrors
        );
    }

    /** @param array<string,mixed> $templates */
    private function loadTemplates(array $templates): void
    {
        foreach ($templates as $name => $t) {
            try {
                $template = WebhookTemplate::fromArray($name, (array)$t);
                $this->templates[$name] = $template;
            } catch (Throwable $e) {
                throw new InvalidArgumentException("Invalid template '$name': " . $e->getMessage(), 0, $e);
            }
        }
    }
}