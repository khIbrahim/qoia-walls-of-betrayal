<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;

interface ServerRepositoryInterface extends RepositoryInterface
{

    public function load(Closure $onSuccess, Closure $onFailure): void;

    public function insertDefault(Closure $onSuccess, Closure $onFailure): void;

}