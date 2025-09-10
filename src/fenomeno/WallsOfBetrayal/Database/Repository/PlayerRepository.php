<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\IncrementDeathPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\IncrementKillsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\InsertPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\LoadPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\SetPlayerKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\UpdatePlayerAbilities;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\DTO\PlayerData;
use fenomeno\WallsOfBetrayal\Exceptions\RecordNotFoundException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use Throwable;

class PlayerRepository implements PlayerRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_PLAYERS, [], function (){
            $this->main->getLogger()->info("§aTable `players` has been successfully init");
        });
    }

    public function load(LoadPlayerPayload $payload): Promise
    {
        $resolver = new PromiseResolver();

        Await::f2c(function() use ($resolver, $payload) {
            try {
                $data = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_PLAYER, $payload->jsonSerialize());
                if (empty($data)){
                    $this->insert(
                        new InsertPlayerPayload($payload->uuid, strtolower($payload->name)),
                        fn() => $this->main->getLogger()->info("§a$payload->name successfully inserted."),
                        fn(Throwable $e) => $this->main->getLogger()->info("§aFailed to insert: $payload->name: " . $e->getMessage()),
                    );
                    $resolver->resolve(null);
                    return;
                }

                $data = $data[0];
                if(! isset($data['kingdom'], $data['abilities'])){
                    $resolver->resolve(null);
                    return;
                }

                $kingdom   = (string) $data['kingdom'];
                $abilities = json_decode(((string) $data['abilities']), true);
                $kills     = (int) ($data['kills'] ?? 0);
                $deaths    = (int) ($data['deaths'] ?? 0);

                $resolver->resolve(new PlayerData(
                    uuid: $payload->uuid,
                    name: $payload->name,
                    kingdom: $kingdom,
                    abilities: $abilities,
                    kills: $kills,
                    deaths: $deaths
                ));
            } catch (Throwable $e){
                $this->main->getLogger()->error("§cFailed to load player data : " . $e->getMessage());
                $this->main->getLogger()->logException($e);

                $resolver->reject();
            }
        });

        return $resolver->getPromise();
    }

    public function insert(InsertPlayerPayload $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
    {
        $this->main->getDatabaseManager()->executeInsert(Statements::INSERT_PLAYER, $payload->jsonSerialize(), $onSuccess, $onFailure);
    }


    public function updatePlayerKingdom(SetPlayerKingdomPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::SET_KINGDOM_PLAYER, $payload->jsonSerialize());
    }

    public function updatePlayerAbilities(UpdatePlayerAbilities $payload, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
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

    public function addKill(IncrementKillsPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::INCREMENT_KILLS, $payload->jsonSerialize());
    }

    public function addDeath(IncrementDeathPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::INCREMENT_DEATHS, $payload->jsonSerialize());
    }

    public function asyncLoad(UsernamePayload $payload): Generator
    {
        $data = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_PLAYER_BY_NAME, $payload->jsonSerialize());

        if (empty($data)) {
            throw new RecordNotFoundException("Player with username $payload->username not found.");
        }

        $data = $data[0];

        $kingdom   = (string) $data['kingdom'] ?? '';
        $abilities = json_decode(((string) $data['abilities']), true);
        $kills     = (int) ($data['kills'] ?? 0);
        $deaths    = (int) ($data['deaths'] ?? 0);

        return new PlayerData(
            uuid: (string)$data['uuid'],
            name: (string)$data['name'],
            kingdom: $kingdom,
            abilities: $abilities,
            kills: $kills,
            deaths: $deaths
        );
    }

    /**
     * @throws RecordNotFoundException
     */
    public function getUuidAndUsernameByName(string $targetName): Generator
    {
        $target = $this->main->getServer()->getPlayerExact($targetName);
        if ($target instanceof Player) {
            return [$target->getUniqueId()->toString(), $target->getName()];
        }

        /** @var PlayerData $data */
        $data = yield from $this->asyncLoad(new UsernamePayload(strtolower($targetName)));
        if ($data === null) {
            throw new RecordNotFoundException("Player with username $targetName not found.");
        }

        return [$data->uuid, $data->name];
    }

    public static function getQueriesFile(): string
    {
        return 'queries/mysql/players.sql';
    }
}