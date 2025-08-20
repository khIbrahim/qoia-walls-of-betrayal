<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Punishment;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;

final readonly class UpdateQuestJailPayload implements PayloadInterface
{

    public function __construct(
        public string $target,
        public int $questProgress,
        public int $questObjective
    ){}

    public function jsonSerialize(): array
    {
        return [
            'target'          => $this->target,
            'quest_progress'  => $this->questProgress,
            'quest_objective' => $this->questObjective
        ];
    }
}