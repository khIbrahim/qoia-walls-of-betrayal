<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\thread;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class WebhookRecvQueue extends ThreadSafe
{

    private ThreadSafeArray $results;

    public function __construct()
    {
        $this->results = new ThreadSafeArray();
    }

    public function fetchAllResults(): array
    {
        return $this->synchronized(function(): array {
            $out = [];
            while ($row = $this->results->shift()) {
                $out[] = unserialize($row, ["allowed_classes" => true]);
            }

            return $out;
        });
    }

    public function publishResult(int $jobId, array $data): void
    {
        $this->synchronized(function () use ($jobId, $data): void {
            $this->results[] = serialize([$jobId, $data]);
            $this->notify();
        });
    }

}