<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

use fenomeno\WallsOfBetrayal\Database\DatabaseManager;

interface RepositoryInterface
{

    public function init(DatabaseManager $database): void;

    /**
     * @return string[]
     *
     * [
     *   'mysql'  => ['file1.sql', 'file2.sql'],
     *   'sqlite' => ['file1.sql', 'file3.sql']
     * ]
     *
     */
    public static function getQueriesFiles(): array;

//    /**
//     * @see self::getQueriesFiles()
//     * @deprecated use getQueriesFiles() instead
//     */
//    public static function getQueriesFile(): string;

}