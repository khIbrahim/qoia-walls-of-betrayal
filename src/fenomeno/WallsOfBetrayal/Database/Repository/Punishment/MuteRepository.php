<?php
namespace fenomeno\WallsOfBetrayal\Database\Repository\Punishment;

use Exception;
use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;
use fenomeno\WallsOfBetrayal\Class\Punishment\Mute;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PunishmentRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\HistoryPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\DTO\SanctionHistoryEntry;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

class MuteRepository implements PunishmentRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_MUTE, [], function() {
            $this->main->getLogger()->info("§aTable `mute` has been successfully init");
        });
    }

    public function getAll(): Generator
    {
        return Await::promise(function ($resolve, $reject){
            Await::f2c(function () use($resolve, $reject){
                try {
                    $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::MUTE_GET);
                    $mutes = [];
                    foreach ($rows as $row) {
                        try {
                            $mutes[$row["target"]] = new Mute(
                                target: $row["target"],
                                reason: $row["reason"],
                                staff: $row["staff"],
                                expiration: $row["expiration"],
                                id: $row["id"],
                                createdAt: $row["created_at"],
                                active: $row["active"],
                            );
                        } catch (Exception $e) {
                            $this->main->getLogger()->error("Failed to create Mute object for target {$row['target']}: " . $e->getMessage());
                            $this->main->getLogger()->logException($e);
                        }
                    }

                    $resolve($mutes);
                } catch (Throwable $e) {
                    $reject($e);
                }
            });
        });
    }

    public function create(AbstractPunishment $punishment): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($punishment) {
            $this->main->getDatabaseManager()->executeInsert(Statements::MUTE_CREATE, $punishment->toArray(), function (int $insertId) use ($resolve, $punishment){
                $punishment->setId($insertId);
                $resolve($punishment);
            }, function (Throwable $e) use ($reject) {
                $this->main->getLogger()->error("[DB] mute.create failed: " . $e->getMessage());
                $reject($e);
            });
        });
    }

    public function delete(UsernamePayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::MUTE_DELETE, $payload->jsonSerialize());
    }

    public function getHistory(HistoryPayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload) {
            Await::f2c(function () use ($payload, $resolve, $reject) {
                try {
                    $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::HISTORY_GET, $payload->jsonSerialize());

                    $history = [];
                    foreach ($rows as $row) {
                        $history[] = new SanctionHistoryEntry(
                            target: $row["target"],
                            type: AbstractPunishment::TYPE_MUTE,
                            reason: $row["reason"],
                            staff: $row["staff"],
                            createdAt: $row["created_at"],
                            expiration: $row["expiration"] !== null ? (int)$row["expiration"] : null
                        );
                    }

                    $resolve($history);
                } catch (Throwable $e){
                    $reject($e);
                }
            });
        });
    }
}