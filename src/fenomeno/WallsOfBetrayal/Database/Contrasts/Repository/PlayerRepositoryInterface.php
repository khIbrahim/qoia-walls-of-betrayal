<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\IncrementDeathPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\IncrementKillsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\InsertPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\LoadPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\SetPlayerKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\UpdatePlayerAbilities;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\UpdatePlayerStatsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\DTO\PlayerData;
use fenomeno\WallsOfBetrayal\Exceptions\RecordNotFoundException;
use Generator;
use pocketmine\promise\Promise;

interface PlayerRepositoryInterface extends RepositoryInterface
{

    /**
     * Charge les données d’un joueur depuis la base
     *
     * @param LoadPlayerPayload $payload
     *
     * @phpstan-template TPromiseValue of null|PlayerData
     *
     * @phpstan-return Promise<TPromiseValue> Renvoie un PlayerData si les données existent, sinon null
     */
    public function load(LoadPlayerPayload $payload): Promise; //todo generator

    public function insert(InsertPlayerPayload $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void;

    public function updatePlayerKingdom(SetPlayerKingdomPayload $payload): Generator;

    public function updatePlayerAbilities(UpdatePlayerAbilities $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void;

    public function addKill(IncrementKillsPayload $payload): Generator;

    public function addDeath(IncrementDeathPayload $payload): Generator;

    /**
     * @throws RecordNotFoundException
     */
    public function asyncLoad(UsernamePayload $payload): Generator;

    public function getUuidAndUsernameByName(string $targetName): Generator;

    public function updateStats(UpdatePlayerStatsPayload $payload): Generator;

}