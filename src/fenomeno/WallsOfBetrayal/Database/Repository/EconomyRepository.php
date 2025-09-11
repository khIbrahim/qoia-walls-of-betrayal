<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\EconomyRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\AddEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\GetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\InsertEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SubtractEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TopEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TransferEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyBalanceIsSameException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordMissingDatException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InsufficientFundsException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

class EconomyRepository implements EconomyRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_ECONOMY, [], function (){
            $this->main->getLogger()->info("§aTable `economy` has been successfully init");
        });
    }

    public function get(GetEconomyPayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload) {
            $this->main->getDatabaseManager()->executeSelect(Statements::GET_ECONOMY, $payload->jsonSerialize(), function (array $rows) use ($resolve, $payload, $reject) {
                try {
                    if ($rows === []){
                        $reject(new EconomyRecordNotFoundException("Account not found for uuid " . $payload->uuid . " or username " . $payload->username));
                        return;
                    }

                    $row = $rows[0];
                    if(! isset($row['amount'], $row['username'], $row['uuid'])){
                        $reject(new EconomyRecordMissingDatException("Account for uuid " . $payload->uuid . " or username " . $payload->username . " is missing data : (amount, username, uuid, position)"));
                        return;
                    }

                    $entry = new EconomyEntry(
                        username: (string) $row['username'],
                        uuid: (string) $row['uuid'],
                        amount: (int) $row['amount'],
                        position: $row['position']
                    );
                    $resolve($entry);
                } catch (Throwable $e){
                    $reject(new DatabaseException(Statements::GET_ECONOMY . " failed", 0, $e));
                }
            }, $reject);
        });
    }

    public function insert(InsertEconomyPayload $payload): Generator
    {
        return Await::promise(fn ($resolve, $reject) => $this->main->getDatabaseManager()->executeInsert(Statements::INSERT_ECONOMY, $payload->jsonSerialize(), $resolve, $reject));
    }

    public function add(AddEconomyPayload $payload): Generator {
        return Await::promise(function ($resolve, $reject) use ($payload): void {
            $this->main->getDatabaseManager()->executeChange(
                Statements::ADD_ECONOMY,
                $payload->jsonSerialize(),
                function (int $affectedRows) use ($resolve, $payload, $reject): void {
                    if ($affectedRows === 0) {
                        $reject(new EconomyRecordNotFoundException("Account not found for uuid " . ($payload->uuid ?? 'null') . " or username " . ($payload->username ?? 'null')));
                        return;
                    }

                    $resolve($affectedRows);
                },
                fn(Throwable $e) => $reject(new DatabaseException(Statements::ADD_ECONOMY . " failed", 0, $e))
            );
        });
    }

    public function subtract(SubtractEconomyPayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload): void {
            $this->main->getDatabaseManager()->executeChange(
                Statements::SUBTRACT_ECONOMY,
                $payload->jsonSerialize(),
                function (int $affectedRows) use ($resolve, $payload, $reject): void {
                    if ($affectedRows === 0) {
                        $reject(new InsufficientFundsException("Insufficient funds for uuid " . ($payload->uuid ?? 'null') . " or username " . ($payload->username ?? 'null')));
                        return;
                    }

                    $resolve($affectedRows);
                },
                fn(Throwable $e) => $reject(new DatabaseException(Statements::SUBTRACT_ECONOMY . " failed", 0, $e))
            );
        });
    }

    public function transfer(TransferEconomyPayload $payload): \Generator {
        return Await::promise(function ($resolve, $reject) use ($payload): void {
            $db = $this->main->getDatabaseManager();

            $db->executeGeneric(Statements::TRANSFER_BEGIN, [], function () use ($db, $payload, $resolve, $reject): void {

                $db->executeChange(Statements::TRANSFER_DEBIT_SENDER, [
                    's_uuid' => $payload->senderUuid,
                    's_name' => $payload->senderUsername,
                    'amount' => $payload->amount
                ], function (int $affectedRows) use ($db, $payload, $resolve, $reject): void {
                    if ($affectedRows < 1) {
                        $db->executeGeneric(Statements::TRANSFER_ROLLBACK);
                        $reject(new InsufficientFundsException("Insufficient funds or sender not found"));
                        return;
                    }

                    $db->executeChange(Statements::CREDIT_RECEIVER, [
                        'r_uuid' => $payload->receiverUuid,
                        'r_name' => $payload->receiverUsername,
                        'amount' => $payload->amount
                    ], function (int $affectedRows) use ($db, $resolve, $reject): void {
                        if ($affectedRows < 1) {
                            $db->executeGeneric(Statements::TRANSFER_ROLLBACK);
                            $reject(new EconomyRecordNotFoundException("Receiver not found"));
                            return;
                        }

                        $db->executeGeneric(Statements::TRANSFER_COMMIT, [], function () use ($resolve): void {
                            $resolve();
                        }, function (\Throwable $e) use ($reject): void {
                            $reject(new DatabaseException(Statements::TRANSFER_COMMIT . " failed", 0, $e));
                        });
                    }, function (\Throwable $e) use ($db, $reject): void {
                        $db->executeGeneric(Statements::TRANSFER_ROLLBACK);
                        $reject(new DatabaseException(Statements::CREDIT_RECEIVER . " failed", 0, $e));
                    });
                }, function (\Throwable $e) use ($db, $reject): void {
                    $db->executeGeneric(Statements::TRANSFER_ROLLBACK);
                    $reject(new DatabaseException(Statements::TRANSFER_DEBIT_SENDER . " failed", 0, $e));
                });
            }, function (\Throwable $e) use ($reject): void {
                $reject(new DatabaseException(Statements::TRANSFER_BEGIN . " failed", 0, $e));
            });
        });
    }

    public function top(TopEconomyPayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload){
            $this->main->getDatabaseManager()->executeSelect(
                Statements::TOP_ECONOMY,
                $payload->jsonSerialize(),
                function(array $rows) use ($resolve): void {
                    $list = [];
                    foreach ($rows as $row) {
                        $list[] = new EconomyEntry(
                            username: (string)$row['username'],
                            uuid:     (string)$row['uuid'],
                            amount:   (int)$row['amount'],
                            position: isset($row['position']) ? (int)$row['position'] : null
                        );
                    }
                    $resolve($list);
                },
                fn(Throwable $e) => $reject(new DatabaseException(Statements::TOP_ECONOMY . " failed", 0, $e))
            );
        });
    }

    /** @throws */
    public function set(SetEconomyPayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload): void {
            $this->main->getDatabaseManager()->executeChange(
                Statements::SET_ECONOMY,
                $payload->jsonSerialize(),
                function (int $affectedRows) use ($resolve, $payload, $reject): void {
                    $this->resolveSetResult($affectedRows, $payload, $resolve, $reject);
                },
                fn(Throwable $e) => $reject(new DatabaseException(Statements::SET_ECONOMY . " failed", 0, $e))
            );
        });
    }

    /** @throws */
    private function resolveSetResult(int $affectedRows, SetEconomyPayload $payload, callable $resolve, callable $reject): void {
        if ($affectedRows > 0) {
            $resolve();
            return;
        }

        Await::g2c(
            $this->get(new GetEconomyPayload($payload->username)),
            function (EconomyEntry $_) use ($payload, $reject) {
                $reject(new EconomyBalanceIsSameException("Balance is already set to " . $payload->amount . " for user " . $payload->username));
            },
            function (Throwable $_) use ($payload, $reject) {
                $reject(new EconomyRecordNotFoundException("Account not found for " . $payload->username));
            }
        );
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/economy.sql'
            ]
        ];
    }
}