<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository\Punishment;

use Exception;
use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;
use fenomeno\WallsOfBetrayal\Class\Punishment\Ban;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PunishmentRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

class BanRepository implements PunishmentRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_BAN, [], function() {
            $this->main->getLogger()->info("§aTable `ban` has been successfully initialized");
        });
    }

    public function getAll(): Generator
    {
        return Await::promise(function ($resolve, $reject){
            Await::f2c(function () use ($resolve, $reject){
                try {
                    $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::BAN_GETALL);
                    $bans = [];
                    foreach ($rows as $row){
                        try {
                            $bans[$row['target']] = new Ban(
                                target: $row["target"],
                                reason: $row["reason"],
                                staff: $row["staff"],
                                expiration: $row["expiration"] !== null ? (int)$row["expiration"] : null,
                                silent: (bool) $row["silent"],
                                id: $row["id"],
                                createdAt: $row["created_at"],
                                active: (bool) ($row["active"] ?? true)
                            );
                        } catch (Exception $e) {
                            $this->main->getLogger()->error("Failed to create Ban object for target {$row['target']}: " . $e->getMessage());
                        }
                    }

                    $resolve($bans);
                } catch (Throwable $e) {
                    $reject($e);
                }
            });
        });
    }

    public function create(AbstractPunishment $punishment): Generator
    {

        return Await::promise(function ($resolve, $reject) use ($punishment) {
            $this->main->getDatabaseManager()->executeInsert(Statements::BAN_ADD, $punishment->toArray(), function (int $insertId) use ($resolve, $punishment){
                $punishment->setId($insertId);
                $resolve($punishment);
            }, $reject);
        });
    }

    public function delete(UsernamePayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::BAN_REMOVE, $payload->jsonSerialize());
    }

    public static function getQueriesFile(): string
    {
        return 'queries/mysql/ban.sql';
    }
}