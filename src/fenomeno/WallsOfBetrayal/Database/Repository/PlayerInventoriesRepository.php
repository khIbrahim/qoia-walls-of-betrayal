<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerInventoriesRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory\LoadPlayerInventoriesPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory\SavePlayerInventoriesPayload;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\DTO\SavedPlayerInventories;
use fenomeno\WallsOfBetrayal\Main;
use Generator;

class PlayerInventoriesRepository implements PlayerInventoriesRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_PLAYER_INVENTORIES, [], function () use ($database) {
            $this->main->getLogger()->info("Table `player_inventories` has been successfully init.");
        });
    }

    /**
     * @return Generator<SavedPlayerInventories|null>
     */
    public function load(LoadPlayerInventoriesPayload $payload): Generator
    {
        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::LOAD_PLAYER_INVENTORIES, $payload->jsonSerialize());

        if (count($rows) === 0) {
            return null;
        }

        $row     = $rows[0];
        $inv     = $this->main->getDatabaseManager()->readItems($this->main->getDatabaseManager()->getBinaryStringParser()->decode($row["inventory"]), self::TAG_INVENTORY . $payload->context);
        $armor   = $this->main->getDatabaseManager()->readItems($this->main->getDatabaseManager()->getBinaryStringParser()->decode($row["armor"]), self::TAG_ARMOR_INVENTORY . $payload->context);
        $offhand = $this->main->getDatabaseManager()->readItems($this->main->getDatabaseManager()->getBinaryStringParser()->decode($row["offhand"]), self::TAG_OFF_HAND_INV . $payload->context);

        return new SavedPlayerInventories(
            $inv,
            $armor,
            $offhand,
            $payload->context
        );
    }

    public function save(SavePlayerInventoriesPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::SAVE_PLAYER_INVENTORIES, $payload->jsonSerialize());
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/player_inventories.sql'
            ]
        ];
    }
}