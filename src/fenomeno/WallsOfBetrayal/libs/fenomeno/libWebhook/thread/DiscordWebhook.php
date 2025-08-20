<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\thread;

use Closure;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\exceptions\WebhookPoolException;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\model\WebhookMessage;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\WebhookPool;
use Generator;
use pocketmine\plugin\PluginBase;

class DiscordWebhook
{

    private static ?WebhookPool $pool = null;

    public static function init(PluginBase $plugin): void
    {
        if (self::$pool !== null) {
            return;
        }

        self::$pool = new WebhookPool($plugin);
    }

    public static function isRegistered(): bool
    {
        return self::$pool !== null;
    }

    /**
     * Access the pool (with a friendly error if not initialized).
     */
    public static function pool(): WebhookPool
    {
        if (self::$pool === null) {
            throw new \RuntimeException(
                "libWebhook not initialized. Call DiscordWebhook::init(\$this) in onEnable()."
            );
        }
        return self::$pool;
    }

    /**
     * Convenience helper to submit a message.
     * @throws WebhookPoolException
     */
    public static function submit(
        string         $webhookUrl,
        WebhookMessage $message,
        ?Closure       $onSuccess = null,
        ?Closure       $onFailure = null
    ): int
    {
        return self::pool()->submit($webhookUrl, $message, $onSuccess, $onFailure);
    }

    /**
     * Convenience helper to submit from a template.
     * @throws WebhookPoolException
     */
    public static function submitFromTemplate(
        string   $webhookUrl,
        string   $templateName,
        array    $variables = [],
        ?Closure $onSuccess = null,
        ?Closure $onFailure = null
    ): int
    {
        return self::pool()->submitFromTemplate($webhookUrl, $templateName, $variables, $onSuccess, $onFailure);
    }

    /**
     * Await-style helper (if you use SOFe\Await).
     * @param string $webhookUrl
     * @param WebhookMessage $message
     * @return Generator
     * @throws WebhookPoolException
     */
    public static function asyncSubmit(string $webhookUrl, WebhookMessage $message): Generator
    {
        return self::pool()->asyncSubmit($webhookUrl, $message);
    }

    /**
     * Await-style helper to submit from a template (if you use SOFe\Await).
     * @param string $webhookUrl
     * @param string $templateName
     * @param array $variables
     * @return Generator
     * @throws WebhookPoolException
     */
    public static function asyncSubmitFromTemplate(
        string $webhookUrl,
        string $templateName,
        array  $variables = []
    ): Generator
    {
        return self::pool()->asyncSubmitFromTemplate($webhookUrl, $templateName, $variables);
    }

    /**
     * Shutdown the pool and release resources.
     * This should be called in onDisable() to ensure graceful shutdown.
     */
    public static function shutdown(): void
    {
        if (self::$pool !== null) {
            self::$pool->shutdown();
            self::$pool = null;
        }
    }
}