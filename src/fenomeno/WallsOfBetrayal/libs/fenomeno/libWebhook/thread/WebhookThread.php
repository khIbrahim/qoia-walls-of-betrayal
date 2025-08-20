<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\thread;

use Closure;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\config\WebhookConfiguration;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\config\WebhookLogger;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use RuntimeException;
use Throwable;

class WebhookThread extends Thread
{

    private SleeperHandlerEntry $sleeperEntry;

    private WebhookSendQueue $bufferSend;
    private WebhookRecvQueue $bufferRecv;

    private bool $running = false;

    private int $connectTimeoutMs;
    private int $requestTimeoutMs;
    private bool $verifyPeer;
    private bool $verifyHost;
    private string $userAgent;

    private WebhookLogger $logger;

    public function __construct(
        Closure $tickHandler,
        WebhookConfiguration $configuration,
        WebhookLogger $logger,
        WebhookSendQueue $bufferSend = null,
        WebhookRecvQueue $bufferRecv = null,
    ){
        $this->sleeperEntry = Server::getInstance()->getTickSleeper()->addNotifier($tickHandler);

        $this->bufferSend = $bufferSend ?? new WebhookSendQueue($configuration->maxQueueSize);
        $this->bufferRecv = $bufferRecv ?? new WebhookRecvQueue();

        $this->connectTimeoutMs = $configuration->connectTimeoutMs;
        $this->requestTimeoutMs = $configuration->requestTimeoutMs;
        $this->verifyPeer       = $configuration->verifyPeer;
        $this->verifyHost       = $configuration->verifyHost;
        $this->userAgent        = $configuration->userAgent;

        $this->logger = $logger;

        $this->start(Thread::INHERIT_INI);
        $this->logger->info("Webhook thread started with user agent: $this->userAgent");
    }

    protected function onRun(): void
    {
        $this->running = true;
        $notifier      = $this->sleeperEntry->createNotifier();

        while ($this->running){
            $row = $this->bufferSend->fetchJob();

            if(! is_string($row)){
                continue;
            }

            [$jobId, $webhook, $payloadJson] = unserialize($row, ["allowed_classes" => false]);

            try {
                [$status, $retryAfterMs, $body, $errorMsg] = $this->postDiscord($webhook, $payloadJson);

                if ($status >= 200 && $status < 300) {
                    $this->bufferRecv->publishResult($jobId, [
                        'ok'           => true,
                        'status'       => $status,
                        'retry_after'  => $retryAfterMs,
                        'body'         => $body,
                    ]);
                } else {
                    $this->bufferRecv->publishResult($jobId, [
                        'ok'           => false,
                        'status'       => $status,
                        'retry_after'  => $retryAfterMs,
                        'error'        => $errorMsg
                    ]);
                }
            } catch (Throwable $e){
                $this->bufferRecv->publishResult($jobId, [
                    'ok'           => false,
                    'status'       => 0,
                    'retry_after'  => 0,
                    'error'        => $e->getMessage()
                ]);
            }

            $notifier->wakeupSleeper();
        }
    }

    public function quit(): void
    {
        $this->running = false;
        $this->bufferSend->invalidate();
        parent::quit();
    }

    public function addJob(int $jobId, string $webhook, string $payloadJson): void {
        $this->bufferSend->scheduleJob($jobId, $webhook, $payloadJson);
    }

    /**
     * À voir si je le laisse $callbacks en pointeur ou pas, je pense à faire une méthode
     *
     * @param array $callbacks
     * @return void
     */
    public function readResults(array &$callbacks): void
    {
        $list = $this->bufferRecv->fetchAllResults();

        foreach ($list as [$jobId, $payload]) {
            if(! isset($callbacks[$jobId])){ // jsp comment ça pourrait arriver mais bon
                throw new RuntimeException("No callback registered for job ID $jobId");
            }

            $callbacks[$jobId]($payload);
            unset($callbacks[$jobId]);
        }
    }

    private function postDiscord(string $webhook, string $payloadJson): array
    {
        $recvHeaders = [];
        $headerFn = static function ($ch, string $headerLine) use (&$recvHeaders): int {
            $parts = explode(':', $headerLine, 2);
            if (count($parts) === 2) {
                $recvHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($headerLine);
        };

        $ch = curl_init($webhook);
        if ($ch === false) {
            throw new RuntimeException("Failed to initialize cURL session.");
        }

        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $payloadJson,
            CURLOPT_HTTPHEADER      => [
                "Content-Type: application/json",
                "User-Agent: $this->userAgent",
            ],
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADERFUNCTION  => $headerFn,
            CURLOPT_CONNECTTIMEOUT_MS => $this->connectTimeoutMs,
            CURLOPT_TIMEOUT_MS         => $this->requestTimeoutMs,
            CURLOPT_SSL_VERIFYHOST  => $this->verifyHost ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER  => $this->verifyPeer,
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = $body === false ? curl_error($ch) : "HTTP $status";
        curl_close($ch);

        $retryAfterMs = 0;
        if(isset($recvHeaders['retry-after'])) {
            $val = trim($recvHeaders['retry-after']);

            if($val !== '' && is_numeric($val)){
                $retryAfterMs = (int) $val;

                if($retryAfterMs < 1000){
                    $retryAfterMs *= 1000;
                }

                if($this->logger->shouldLogRateLimits()){
                    $this->logger->warning("429 Retry-After {$retryAfterMs}ms");
                }
            }
        }
        return [$status, $retryAfterMs, is_string($body) ? $body : null, $err];
    }
}