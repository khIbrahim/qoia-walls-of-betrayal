<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\CooldownRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\GetActiveCooldownsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\RemoveCooldownPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\UpsertCooldownPayload;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\DTO\CooldownEntry;
use fenomeno\WallsOfBetrayal\Main;

class CooldownRepository implements CooldownRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_COOLDOWNS, [], function (){
            $this->main->getLogger()->info("§aTable `cooldowns` has been successfully init");
        });
    }

    public function getAll(GetActiveCooldownsPayload $payload): \Generator {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(
            Statements::GET_ACTIVE_COOLDOWNS,
            $payload->jsonSerialize()
        );

        $entries = [];
        foreach ($rows as $i => $row) {
            if (!isset($row['identifier'], $row['cooldown_type'], $row['expiry_time'])) {
                $this->main->getLogger()->error("Failed to fetch cooldown entry ($i): Missing data (identifier?, cooldown_type?, expiry_time?)");
                continue;
            }
            $entries[] = new CooldownEntry(
                identifier: (string) $row['identifier'],
                type: (string) $row['cooldown_type'],
                expiryTime: (int) $row['expiry_time']
            );
        }
        return $entries;
    }

    public function upsert(UpsertCooldownPayload $payload): \Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::UPSERT_COOLDOWN, $payload->jsonSerialize());
    }

    public function remove(RemoveCooldownPayload $payload): \Generator
    {
        yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::REMOVE_COOLDOWN, $payload->jsonSerialize());
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/cooldowns.sql'
            ]
        ];
    }
}