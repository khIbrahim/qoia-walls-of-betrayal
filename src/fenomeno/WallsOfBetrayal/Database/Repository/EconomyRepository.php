<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\EconomyRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\AddEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\GetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\InsertEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SubtractEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TopEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TransferEconomyPayload;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordMissingDatException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InsufficientFundsException;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\SqlThread;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

class EconomyRepository implements EconomyRepositoryInterface
{

    public function __construct(private readonly Main $main){}

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

                    $resolve();
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

                    $resolve();
                },
                fn(Throwable $e) => $reject(new DatabaseException(Statements::SUBTRACT_ECONOMY . " failed", 0, $e))
            );
        });
    }

    public function transfer(TransferEconomyPayload $payload): Generator {
        return Await::promise(function ($resolve, $reject) use ($payload): void {
            $db = $this->main->getDatabaseManager();

            $db->executeChange(
                Statements::TRANSFER_ECONOMY,
                $payload->jsonSerialize(),
                function (int $affectedRows) use ($resolve, $reject, $payload): void {
                    if ($affectedRows >= 2) {
                        $resolve();
                        return;
                    }

                    Await::g2c(
                        $this->get(new GetEconomyPayload(username: null, uuid: $payload->senderUuid)),
                        function (EconomyEntry $sender) use ($payload, $resolve, $reject): void {
                            if ($sender->amount < $payload->amount) {
                                $reject(new InsufficientFundsException("Insufficient funds"));
                                return;
                            }

                            Await::g2c(
                                $this->get(new GetEconomyPayload(username: null, uuid: $payload->receiverUuid)),
                                function (EconomyEntry $_receiver) use ($reject): void {
                                    $reject(new DatabaseException(Statements::TRANSFER_ECONOMY . " failed for unknown reason"));
                                },
                                function (Throwable $_e) use ($reject): void {
                                    $reject(new EconomyRecordNotFoundException("Receiver not found"));
                                }
                            );
                        },
                        function (Throwable $_e) use ($reject): void {
                            $reject(new EconomyRecordNotFoundException("Sender not found"));
                        }
                    );
                },
                fn (Throwable $e) => $reject(new DatabaseException(Statements::TRANSFER_ECONOMY . " failed", 0, $e))
            );
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

    public function set(SetEconomyPayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload): void {
            $this->main->getDatabaseManager()->executeChange(
                Statements::SET_ECONOMY,
                $payload->jsonSerialize(),
                function (int $affectedRows) use ($resolve, $payload, $reject): void {
                    if ($affectedRows === 0) {
                        $reject(new EconomyRecordNotFoundException("Account not found for uuid " . ($payload->uuid ?? 'null') . " or username " . ($payload->username ?? 'null')));
                        return;
                    }
                    $resolve();
                },
                fn(Throwable $e) => $reject(new DatabaseException(Statements::ADD_ECONOMY . " failed", 0, $e))
            );
        });
    }
}