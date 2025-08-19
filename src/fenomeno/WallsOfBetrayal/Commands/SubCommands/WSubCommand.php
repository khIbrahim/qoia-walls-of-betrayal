<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands;

use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\BaseSubCommand;
use fenomeno\WallsOfBetrayal\Main;

abstract class WSubCommand extends BaseSubCommand
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
    }

    abstract public function getCommandDTO(): CommandDTO;

}