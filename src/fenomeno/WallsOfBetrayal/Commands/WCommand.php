<?php

namespace fenomeno\WallsOfBetrayal\Commands;

use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\BaseCommand;
use fenomeno\WallsOfBetrayal\Main;

abstract class WCommand extends BaseCommand
{
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
        $this->setPermission('wob.command.' . strtolower($dto->name));
    }

    abstract public function getCommandDTO(): CommandDTO;
}