<?php
namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\builder;

use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\model\WebhookMessage;

final class WebhookMessageBuilder
{
    private string $content = '';
    private string $username = 'libWebhook';
    private ?string $avatarUrl = null;
    private array $allowedMentions = ['parse' => []];
    private array $embeds = [];

    public static function create(): self
    {
        return new self();
    }

    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function username(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function avatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function allowedMentions(array $allowedMentions): self
    {
        $this->allowedMentions = $allowedMentions;
        return $this;
    }

    public function addEmbed(array $embed): self
    {
        $this->embeds[] = $embed;
        return $this;
    }

    public function embeds(array $embeds): self
    {
        $this->embeds = $embeds;
        return $this;
    }

    public function build(): WebhookMessage
    {
        return new WebhookMessage(
            content: $this->content,
            username: $this->username,
            avatarUrl: $this->avatarUrl,
            allowed: $this->allowedMentions,
            embeds: $this->embeds
        );
    }
}