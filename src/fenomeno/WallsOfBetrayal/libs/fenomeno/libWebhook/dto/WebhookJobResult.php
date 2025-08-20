<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\dto;

final readonly class WebhookJobResult
{
    public function __construct(
        public bool $ok,
        public int $status,
        public int $retryAfter,
        public ?string $body,
        public ?string $error = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ok: (bool) ($data['ok'] ?? false),
            status: (int) ($data['status'] ?? 0),
            retryAfter: (int) ($data['retry_after'] ?? 0),
            body: $data['body'] ?? null,
            error: $data['error'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'ok'          => $this->ok,
            'status'      => $this->status,
            'retry_after' => $this->retryAfter,
            'body'        => $this->body,
            'error'       => $this->error,
        ];
    }

    public function isSuccess(): bool
    {
        return $this->ok;
    }

    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }

    public function shouldRetry(): bool
    {
        return $this->status >= 500 || $this->isRateLimited();
    }
}