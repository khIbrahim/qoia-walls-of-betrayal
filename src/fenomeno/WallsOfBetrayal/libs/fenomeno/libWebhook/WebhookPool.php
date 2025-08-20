<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook;

use Closure;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\config\WebhookConfiguration;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\config\WebhookLogger;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\dto\WebhookJobResult;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\exceptions\WebhookPoolException;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\model\WebhookMessage;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\model\WebhookTemplate;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\thread\WebhookThread;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use Generator;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use Throwable;

final class WebhookPool
{
    use SingletonTrait {
        reset as protected;
        setInstance as private;
    }

    private WebhookConfiguration $configuration;
    private WebhookLogger $logger;

    /** @var WebhookThread[] */
    private array $threads = [];

    /** @var array<int, Closure> */
    private array $callbacks = [];

    private int $nextJobId = 0;
    private int $roundRobinIndex = 0;
    private bool $isShuttingDown = false;

    /** @var array<string, WebhookTemplate> */
    private array $templates;

    public function __construct(PluginBase $plugin)
    {
        self::setInstance($this);
        $this->configuration = new WebhookConfiguration($plugin->getConfig()->get('webhook', []));
        $this->logger        = $this->configuration->createLogger($plugin->getServer()->getLogger());
        $this->templates     = $this->configuration->getTemplates();

        for ($i = 0; $i < $this->configuration->threadCount; $i++) {
            $this->threads[$i] = new WebhookThread(
                tickHandler: fn() => $this->checkResults(),
                configuration: $this->configuration,
                logger: $this->logger
            );
        }

        $this->logger->info("WebhookPool initialized with {$this->configuration->threadCount} threads");
    }

    /**
     * @throws WebhookPoolException
     */
    public function submitRaw(
        string   $webhookUrl,
        string   $payloadJson,
        ?Closure $onSuccess = null,
        ?Closure $onFailure = null
    ): int {
        if ($this->isShuttingDown) {
            throw new WebhookPoolException("Cannot submit jobs: WebhookPool is shutting down");
        }

        $jobId = $this->nextJobId++;

        $this->callbacks[$jobId] = $this->createResultHandler($jobId, $onSuccess, $onFailure);

        $selectedThread = $this->selectThread();
        $selectedThread->addJob($jobId, $webhookUrl, $payloadJson);

        $this->logger->debug("Submitted raw job #$jobId to thread #$this->roundRobinIndex");

        return $jobId;
    }

    /**
     * @throws WebhookPoolException
     */
    public function submit(
        string         $webhookUrl,
        WebhookMessage $message,
        ?Closure       $onSuccess = null,
        ?Closure       $onFailure = null
    ): int {
        return $this->submitRaw(
            $webhookUrl,
            json_encode($message->toDiscordPayload()),
            $onSuccess,
            $onFailure
        );
    }

    /**
     * @throws WebhookPoolException
     */
    public function submitFromTemplate(
        string   $webhookUrl,
        string   $templateName,
        array    $variables = [],
        ?Closure $onSuccess = null,
        ?Closure $onFailure = null
    ): int {
        if (! isset($this->templates[$templateName])) {
            throw new WebhookPoolException("Template '$templateName' not found");
        }

        $message = $this->templates[$templateName]->render($variables);
        return $this->submit($webhookUrl, $message, $onSuccess, $onFailure);
    }

    /**
     * @throws WebhookPoolException
     */
    public function asyncSubmit(string $webhookUrl, WebhookMessage $message): Generator
    {
        $onResolve = yield Await::RESOLVE;
        $onReject = yield Await::REJECT;

        $this->submit(
            $webhookUrl,
            $message,
            function (WebhookJobResult $result) use ($onResolve) {
                $onResolve($result);
            },
            function (WebhookJobResult $result) use ($onReject) {
                $onReject(new WebhookPoolException($result->error ?? 'Unknown error', $result->status));
            }
        );

        return yield Await::ONCE;
    }

    /**
     * @throws WebhookPoolException
     */
    public function asyncSubmitFromTemplate(string $webhookUrl, string $templateName, array $variables = []): Generator {
        if (! isset($this->templates[$templateName])) {
            throw new WebhookPoolException("Template '$templateName' not found");
        }

        $message = $this->templates[$templateName]->render($variables);
        return yield from $this->asyncSubmit($webhookUrl, $message);
    }

    private function selectThread(): WebhookThread
    {
        $thread = $this->threads[$this->roundRobinIndex];
        $this->roundRobinIndex = ($this->roundRobinIndex + 1) % count($this->threads);
        return $thread;
    }

    private function createResultHandler(int $jobId, ?Closure $onSuccess, ?Closure $onFailure): Closure
    {
        return function (array $payload) use ($jobId, $onSuccess, $onFailure): void {
            try {
                $result = WebhookJobResult::fromArray($payload);

                if ($result->ok) {
                    $this->logger->debug("Job #$jobId completed successfully");
                    $onSuccess?->__invoke($result);
                } else {
                    $this->logger->warning("Job #$jobId failed: $result->error (Status: $result->status)");
                    $onFailure?->__invoke($result);
                }
            } catch (Throwable $e) {
                $errorResult = new WebhookJobResult(
                    ok: false,
                    status: 0,
                    retryAfter: 0,
                    body: null,
                    error: $e->getMessage()
                );
                $this->logger->error("Error handling job #$jobId: {$e->getMessage()}");
                $onFailure?->__invoke($errorResult);
            } finally {
                unset($this->callbacks[$jobId]);
            }
        };
    }

    public function checkResults(): void
    {
        if (empty($this->callbacks)) {
            return;
        }

        foreach ($this->threads as $thread) {
            try {
                $thread->readResults($this->callbacks);
            } catch (Throwable $e) {
                $this->logger->error("Error reading results from thread: {$e->getMessage()}");
            }
        }
    }

    public function waitAll(): void
    {
        while (! empty($this->callbacks)) {
            $this->checkResults();
        }
    }

    public function shutdown(): void
    {
        if ($this->isShuttingDown) {
            return;
        }

        $this->isShuttingDown = true;
        $this->logger->info("WebhookPool shutting down...");

        $this->waitAll();

        foreach ($this->threads as $i => $thread) {
            try {
                $thread->quit();
                $this->logger->debug("Thread #$i shut down");
            } catch (Throwable $e) {
                $this->logger->error("Error shutting down thread #$i: {$e->getMessage()}");
            }
        }

        $this->threads = [];
        $this->callbacks = [];
        $this->nextJobId = 0;
        $this->roundRobinIndex = 0;

        $this->logger->info("WebhookPool shutdown completed");
    }
}