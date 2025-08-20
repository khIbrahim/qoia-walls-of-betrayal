<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\thread;

use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\exceptions\QueueOverflowException;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

final class WebhookSendQueue extends ThreadSafe
{

    private bool $invalidated = false;
    private ThreadSafeArray $jobs;

    public function __construct(
        private readonly int $capacity = 2048
    ) {
        $this->jobs = new ThreadSafeArray();
    }

    public function scheduleJob(int $jobId, string $webhook, string $payloadJson): void
    {
        if ($this->invalidated) {
            throw new \RuntimeException("Cannot schedule job, WebhookSendQueue has been invalidated.");
        }

        $this->synchronized(function () use ($jobId, $webhook, $payloadJson): void {
            if ($this->jobs->count() >= $this->capacity) {
                throw new QueueOverflowException("WebhookSendQueue is full, cannot schedule more jobs.");
            }

            $this->jobs[] = serialize([$jobId, $webhook, $payloadJson]);
            $this->notify();
        });
    }

    public function fetchJob(): ?string
    {
        return $this->synchronized(function (): ?string {
            while ($this->jobs->count() === 0 && ! $this->invalidated){
                $this->wait();
            }

            return $this->jobs->shift() ?: null;
        });
    }

    public function invalidate(): void
    {
        $this->synchronized(function (): void {
            $this->invalidated = true;
            $this->notify();
        });
    }

}