<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\InsertPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\LoadPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\SetPlayerKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\UpdatePlayerAbilities;
use fenomeno\WallsOfBetrayal\DTO\PlayerData;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use Throwable;

class PlayerRepository implements PlayerRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function load(LoadPlayerPayload $payload): Promise
    {
        $resolver = new PromiseResolver();

        Await::f2c(function() use ($resolver, $payload) {
            try {
                $data = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_PLAYER, $payload->jsonSerialize());
                if (empty($data)){
                    $resolver->resolve(null);
                    return;
                }

                $data = $data[0];

                $kingdom   = $data['kingdom'] ?? null;
                $abilities = json_decode(($data['abilities'] ?? '[]'), true);

                $resolver->resolve(new PlayerData(
                    kingdom: $kingdom,
                    abilities: $abilities
                ));
            } catch (Throwable $e){
                $this->main->getLogger()->error("§cFailed to load player data : " . $e->getMessage());

                $resolver->reject();
            }
        });

        return $resolver->getPromise();
    }

    public function insert(InsertPlayerPayload $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void
    {
        $this->main->getDatabaseManager()->executeInsert(Statements::INSERT_PLAYER, $payload->jsonSerialize(), $onSuccess, $onFailure);
    }

    /**
     * On insère ici en même temps, c'est la première requête que le joueur fera dans tous les cas
     */
    public function updatePlayerKingdom(SetPlayerKingdomPayload $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void
    {
        Await::f2c(function () use ($onFailure, $onSuccess, $payload) {
            try {
                yield from $this->main->getDatabaseManager()->asyncInsert(Statements::SET_KINGDOM_PLAYER, $payload->jsonSerialize());

                $onSuccess?->__invoke();
            } catch (Throwable $e){
                $onFailure?->__invoke($e);
            }
        });
    }

    public function updatePlayerAbilities(UpdatePlayerAbilities $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void
    {
        Await::f2c(function () use ($onFailure, $onSuccess, $payload) {
            try {
                yield from $this->main->getDatabaseManager()->asyncInsert(Statements::UPDATE_PLAYER_ABILITIES, $payload->jsonSerialize());

                $onSuccess?->__invoke();
            } catch (Throwable $e){
                $onFailure?->__invoke($e);
            }
        });
    }
}