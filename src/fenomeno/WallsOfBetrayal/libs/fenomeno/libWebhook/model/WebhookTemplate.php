<?php
namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\model;

use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\exceptions\TemplateException;

final readonly class WebhookTemplate {

    public function __construct(
        private string  $name,
        private string  $content,
        private string  $username,
        private ?string $avatarUrl,
        private array   $allowed,
        private array   $embeds
    ){}

    /**
     * @throws TemplateException
     */
    public static function fromArray(string $name, array $data): self {
        $allowed = $data['allowed_mentions'] ?? ["parse" => []];

        if(isset($allowed["parse"]) && !is_array($allowed["parse"])){
            throw new TemplateException("allowed_mentions.parse must be array");
        }

        return new self(
            $name,
            (string)($data['content'] ?? '{MESSAGE}'),
            (string)($data['username'] ?? 'libWebhook'),
            isset($data['avatar_url']) && $data['avatar_url'] !== '' ? (string)$data['avatar_url'] : null,
            (array)$allowed,
            (array)($data['embeds'] ?? [])
        );
    }

    /** @param array<string,scalar> $vars */
    public function render(array $vars): WebhookMessage {
        $varsU = [];
        foreach($vars as $k => $v){
            $varsU['{'.strtoupper($k).'}'] = (string)$v;
        }
        $repl = static fn(string $s) => strtr($s, $varsU);

        $content  = $repl($this->content);
        $username = $repl($this->username);
        $avatar   = $this->avatarUrl ? $repl($this->avatarUrl) : null;

        $embeds = $this->embeds;
        foreach($embeds as &$e){
            foreach(["title","description","url"] as $f){
                if(isset($e[$f]) && is_string($e[$f])) $e[$f] = $repl($e[$f]);
            }
            if(isset($e["footer"]["text"])) $e["footer"]["text"] = $repl((string)$e["footer"]["text"]);
            if(isset($e["author"]["name"])) $e["author"]["name"] = $repl((string)$e["author"]["name"]);
            if(isset($e["fields"]) && is_array($e["fields"])){
                foreach($e["fields"] as &$fld){
                    if(isset($fld["name"]))  $fld["name"]  = $repl((string)$fld["name"]);
                    if(isset($fld["value"])) $fld["value"] = $repl((string)$fld["value"]);
                }
                unset($fld);
            }
        }
        unset($e);

        return new WebhookMessage($content, $username, $avatar, $this->allowed, $embeds);
    }
}