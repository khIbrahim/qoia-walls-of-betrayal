<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Class\Player\PlayerLoyalty;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerLoyaltyRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\InsertPlayerLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\PlayerContributeLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\UpdatePlayerLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\Exceptions\RecordNotFoundException;
use fenomeno\WallsOfBetrayal\Main;
use Generator;

class PlayerLoyaltyRepository implements PlayerLoyaltyRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_PLAYERS_LOYALTY, [], function (){
            $this->main->getLogger()->info("§aTable `players_loyalty` has been successfully init");
        });
    }

    public function getLoyalty(UuidPayload $payload, ?InsertPlayerLoyaltyPayload $insertPayload = null): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_PLAYER_LOYALTY, $payload->jsonSerialize());

        if (empty($rows)){
            if ($insertPayload !== null){
                yield from $this->insert($insertPayload);

                return PlayerLoyalty::fromArray($insertPayload->jsonSerialize());
            }

            return null;
        }

        return PlayerLoyalty::fromArray($rows[0]);
    }

    public function updateOrInsertLoyalty(UpdatePlayerLoyaltyPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::UPDATE_PLAYER_LOYALTY, $payload->jsonSerialize());
    }

    public function addContribution(PlayerContributeLoyaltyPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::ADD_PLAYER_LOYALTY_CONTRIBUTION, $payload->jsonSerialize());
    }

    public function insert(InsertPlayerLoyaltyPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::INSERT_PLAYER_LOYALTY, $payload->jsonSerialize());
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/player_loyalty.sql'
            ]
        ];
    }

    public function updateLoyaltyScore(string $uuid, int $score): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_PLAYER_LOYALTY_SCORE, [
            'uuid'  => $uuid,
            'score' => $score
        ]);
    }

    public function getLoyaltyByName(string $username): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_PLAYER_LOYALTY_BY_NAME, [
            'username' => $username
        ]);

        if (empty($rows)){
            throw new RecordNotFoundException("Loyalty record for player $username not found");
        }

        return PlayerLoyalty::fromArray($rows[0]);
    }
}