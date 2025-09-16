<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KillsRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\Logs\Domain\KillLogEvent;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use RuntimeException;

class KillsRepository implements KillsRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_KILLS, [], function (){
            $this->main->getLogger()->info("Table `kills` has been successfully initialized.");
        });
    }

    public function logKill(KillLogEvent $payload): Generator
    {
        [$insertId, $affectedRows] = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::LOG_KILL, $payload->jsonSerialize());

        if($insertId <= 0 || $affectedRows <= 0){
            throw new RuntimeException("Failed to log kill in database.");
        }
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/kills.sql'
            ]
        ];
    }
}