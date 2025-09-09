<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands;

use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\BaseSubCommand;
use fenomeno\WallsOfBetrayal\Main;

abstract class WSubCommand extends BaseSubCommand
{

    public const COOLDOWN_TAG = 'cooldown';

    private array $metadata;

    public function __construct(protected readonly Main $main)
    {
        $dto = $this->getCommandDTO();

        parent::__construct(
            $this->main,
            $dto->name,
            $dto->description,
            $dto->aliases
        );

        $this->setUsage($dto->usage);
        $this->metadata = $dto->metadata;
    }

    abstract public function getCommandDTO(): CommandDTO;

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getCooldown(int $default = 0): int
    {
        $cooldown = $this->getMetadata(self::COOLDOWN_TAG);
        if(is_null($cooldown)){
            $this->metadata[self::COOLDOWN_TAG] = $default;
        }

        return (int) $this->getMetadata(self::COOLDOWN_TAG, $default);
    }

}