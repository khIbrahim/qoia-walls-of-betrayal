<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

use fenomeno\WallsOfBetrayal\Database\Payload\Player\InsertPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\LoadPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\SetPlayerKingdomPayload;
use fenomeno\WallsOfBetrayal\DTO\PlayerData;
use pocketmine\promise\Promise;

interface PlayerRepositoryInterface
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
    public function load(LoadPlayerPayload $payload): Promise;

    public function insert(InsertPlayerPayload $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void;

    public function updatePlayerKingdom(SetPlayerKingdomPayload $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void;

}