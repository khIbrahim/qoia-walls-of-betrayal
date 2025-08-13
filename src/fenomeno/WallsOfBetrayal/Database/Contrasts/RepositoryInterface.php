<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

use fenomeno\WallsOfBetrayal\Database\DatabaseManager;

interface RepositoryInterface
{

    public function init(DatabaseManager $database): void;

}