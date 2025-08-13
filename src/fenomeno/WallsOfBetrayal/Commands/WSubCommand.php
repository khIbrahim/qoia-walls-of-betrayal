<?php

namespace fenomeno\WallsOfBetrayal\Commands;

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
        $this->setPermission($this->getParent()->getPermissions()[0] . '.' . strtolower($dto->name));
    }

    abstract public function getCommandDTO(): CommandDTO;

}