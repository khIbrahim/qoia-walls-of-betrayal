<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository\Punishment;

use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;
use fenomeno\WallsOfBetrayal\Class\Punishment\Report;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PunishmentRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\HistoryPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\DTO\PunishmentHistoryEntry;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

class ReportRepository implements PunishmentRepositoryInterface
{

    public function __construct(private readonly Main $main)
    {
    }

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_REPORT, [], function() {
            $this->main->getLogger()->info("§aTable `reports` has been successfully init");
        });
    }

    public function getAll(): Generator
    {
        return Await::promise(function ($resolve, $reject){
            Await::f2c(function () use ($resolve, $reject){
                try {
                    $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::REPORT_GET);
                    $reports = [];
                    foreach ($rows as $row) {
                        try {
                            $reports[$row['id']] = new Report(
                                target: $row["target"],
                                reason: $row["reason"],
                                staff: $row["staff"],
                                expiration: isset($row["expiration"]) ? (int) $row["expiration"] : null,
                                id: $row["id"],
                                createdAt: $row["created_at"],
                                active: (bool) ($row["active"] ?? true)
                            );
                        } catch (Throwable $e) {
                            $this->main->getLogger()->error("Failed to create Report object for ID {$row['id']}: " . $e->getMessage());
                            $this->main->getLogger()->logException($e);
                        }
                    }

                    $resolve($reports);
                } catch (Throwable $e) {
                    $reject($e);
                }
            });
        });
    }

    public function create(AbstractPunishment $punishment): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($punishment) {
            Await::f2c(function () use ($resolve, $reject, $punishment){
                try {
                    $ret = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::REPORT_CREATE, $punishment->toArray());
                    $insertId = $ret[0] ?? 0;
                    $punishment->setId($insertId);

                    $resolve($punishment);
                } catch (Throwable $e) {
                    $reject($e);
                }
            });
        });
    }

    // azy nique sa mère ça
    public function delete(UsernamePayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::REPORT_DELETE, $payload->jsonSerialize());
    }

    public function del(IdPayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::REPORT_DELETE, $payload->jsonSerialize());
    }

    public function archiveReport(Report $report): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncChange(Statements::REPORT_ARCHIVE, ['id' => $report->getId()]);
    }

    public static function getQueriesFile(): string
    {
        return 'queries/mysql/report.sql';
    }
}