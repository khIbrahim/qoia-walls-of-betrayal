<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\AddEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\GetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\InsertEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SubtractEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TopEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TransferEconomyPayload;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyBalanceIsSameException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordAlreadyExistsException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordMissingDatException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InsufficientFundsException;
use Generator;

interface EconomyRepositoryInterface extends RepositoryInterface
{

    /**
     * @throws EconomyRecordNotFoundException
     * @throws EconomyRecordMissingDatException
     * @throws DatabaseException
     */
    public function get(GetEconomyPayload $payload): Generator;

    /**
     * @throws DatabaseException
     * @throws EconomyRecordAlreadyExistsException
     */
    public function insert(InsertEconomyPayload $payload): Generator;

    /**
     * @throws EconomyRecordNotFoundException
     * @throws DatabaseException
     */
    public function add(AddEconomyPayload $payload): Generator;

    /**
     * @throws InsufficientFundsException
     * @throws EconomyRecordNotFoundException
     */
    public function subtract(SubtractEconomyPayload $payload): Generator;

    /**
     * @throws EconomyRecordNotFoundException
     * @throws InsufficientFundsException
     * @throws DatabaseException
     */
    public function transfer(TransferEconomyPayload $payload): Generator;

    public function top(TopEconomyPayload $payload): Generator;

    /**
     * @throws EconomyBalanceIsSameException
     * @throws EconomyRecordNotFoundException
     */
    public function set(SetEconomyPayload $payload): Generator;

}