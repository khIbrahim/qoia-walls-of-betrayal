<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomBounty;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\BountyRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\CreateKingdomBountyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\DeactivateBountyPayload;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

class KingdomBountyRepository implements BountyRepositoryInterface
{

    public function __construct(private readonly Main $main)
    {
    }

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_KINGDOMS_BOUNTY, [], function () {
            $this->main->getLogger()->info("§aTable `kingdom_bounties` has been successfully init");
        });
    }

    public function loadActives(Closure $onSuccess, Closure $onFailure): void
    {
        $this->main->getDatabaseManager()->executeSelect(Statements::GET_ALL_ACTIVE_KINGDOMS_BOUNTY, [], function (array $rows) use ($onSuccess) {
            if (empty($rows)) {
                return;
            }

            $bounties = [];
            foreach ($rows as $i => $row) {
                try {
                    $bounties[(int)$row['id']] = KingdomBounty::fromArray((array)$row);
                } catch (Throwable $e) {
                    $this->main->getLogger()->error("Failed to parse kingdom bounty $i: " . $e->getMessage());
                }
            }

            $onSuccess($bounties);
        }, $onFailure);
    }

    public function create(CreateKingdomBountyPayload $payload): Generator
    {
        [$insertId,] = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::CREATE_KINGDOMS_BOUNTY, $payload->jsonSerialize());

        return $insertId;
    }

    public function deactivate(DeactivateBountyPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::DEACTIVATE_KINGDOM_BOUNTY, $payload->jsonSerialize());
    }

    public static function getQueriesFile(): string
    {
        return 'queries/mysql/kingdom_bounties.sql';
    }
}