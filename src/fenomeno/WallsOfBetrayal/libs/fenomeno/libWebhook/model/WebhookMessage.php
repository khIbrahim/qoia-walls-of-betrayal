<?php
namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\model;

use JsonSerializable;

final readonly class WebhookMessage implements JsonSerializable {

    public function __construct(
        public string $content,
        public string $username = 'libWebhook',
        public ?string $avatarUrl = null,
        public array $allowed = ["parse" => []],
        public array $embeds = []
    ){}

    public function jsonSerialize(): array {
        $out = [
            "content"  => $this->content,
            "username" => $this->username,
        ];

        if($this->avatarUrl){
            $out["avatar_url"] = $this->avatarUrl;
        }

        if(! empty($this->allowed)){
            $out["allowed_mentions"] = $this->allowed;
        }

        if(! empty($this->embeds)){
            $out["embeds"] = $this->embeds;
        }

        return $out;
    }

    public function toDiscordPayload(): array {
        return $this->jsonSerialize();
    }
}
