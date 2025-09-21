<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\InsertPlayerLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\PlayerContributeLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\UpdatePlayerLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Exceptions\RecordNotFoundException;
use Generator;

interface PlayerLoyaltyRepositoryInterface extends RepositoryInterface
{

    public function getLoyalty(UuidPayload $payload): Generator;

    public function updateOrInsertLoyalty(UpdatePlayerLoyaltyPayload $payload): Generator;

    public function addContribution(PlayerContributeLoyaltyPayload $payload): Generator;

    public function insert(InsertPlayerLoyaltyPayload $payload): Generator;

    public function updateLoyaltyScore(string $uuid, int $score): Generator;

    /**
     * @throws RecordNotFoundException
     */
    public function getLoyaltyByName(string $username): Generator;

}