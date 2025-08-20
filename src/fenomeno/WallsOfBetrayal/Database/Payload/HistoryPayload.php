<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload;

readonly class HistoryPayload extends UsernamePayload
{

    public function __construct(
        string $username,
        public string $type
    ){
        parent::__construct($username);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'type' => $this->type
        ]);
    }

}