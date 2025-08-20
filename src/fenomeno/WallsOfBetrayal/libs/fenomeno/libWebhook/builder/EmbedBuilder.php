<?php

namespace fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\builder;

use DateTime;
use DateTimeInterface;

final class EmbedBuilder
{
    private array $embed = [];

    public static function create(): self
    {
        return new self();
    }

    public function title(string $title): self
    {
        $this->embed['title'] = $title;

        return $this;
    }

    public function description(string $description): self
    {
        $this->embed['description'] = $description;

        return $this;
    }

    public function color(int $color): self
    {
        $this->embed['color'] = $color;

        return $this;
    }

    public function url(string $url): self
    {
        $this->embed['url'] = $url;

        return $this;
    }

    public function timestamp(?DateTimeInterface $timestamp = null): self
    {
        $timestamp = $timestamp ?? new DateTime();

        $this->embed['timestamp'] = $timestamp->format(DateTime::ATOM);

        return $this;
    }

    public function footer(string $text, ?string $iconUrl = null): self
    {
        $this->embed['footer'] = [
            'text' => $text
        ];

        if ($iconUrl !== null) {
            $this->embed['footer']['icon_url'] = $iconUrl;
        }

        return $this;
    }

    public function thumbnail(string $url): self
    {
        $this->embed['thumbnail'] = ['url' => $url];

        return $this;
    }

    public function image(string $url): self
    {
        $this->embed['image'] = ['url' => $url];

        return $this;
    }

    public function author(string $name, ?string $url = null, ?string $iconUrl = null): self
    {
        $this->embed['author'] = ['name' => $name];

        if ($url !== null) {
            $this->embed['author']['url'] = $url;
        }

        if ($iconUrl !== null) {
            $this->embed['author']['icon_url'] = $iconUrl;
        }

        return $this;
    }

    public function field(string $name, string $value, bool $inline = false): self
    {
        if (! isset($this->embed['fields'])) {
            $this->embed['fields'] = [];
        }

        $this->embed['fields'][] = [
            'name' => $name,
            'value' => $value,
            'inline' => $inline,
        ];

        return $this;
    }

    public function build(): array
    {
        return $this->embed;
    }
}