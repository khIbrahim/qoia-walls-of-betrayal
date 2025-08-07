<?php

namespace fenomeno\WallsOfBetrayal\Commands;

use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\BaseCommand;
use fenomeno\WallsOfBetrayal\Main;

abstract class WCommand extends BaseCommand{

    public function __construct(protected readonly Main $main)
    {
        parent::__construct($this->main, $this->getCommandDTO()->name, $this->getCommandDTO()->description, $this->getCommandDTO()->aliases);

        $this->setUsage($this->getCommandDTO()->usage);
        $this->setPermission('command.' . $this->getCommandDTO()->name);
    }

    abstract public function getCommandDTO(): CommandDTO;

}