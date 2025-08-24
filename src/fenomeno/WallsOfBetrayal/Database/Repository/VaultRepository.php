<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\VaultRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\Vault\CloseVaultPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Vault\VaultOpenPayload;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;

class VaultRepository implements VaultRepositoryInterface
{

    private const TAG_INVENTORY = "Inventory";

    private BigEndianNbtSerializer $nbtSerializer;

    /**
     * @var array<string, array<int, Item>> cached
     * cached[username][number] = contents
     */
    private array $cached = [];

    public function __construct(private readonly Main $main){
        $this->nbtSerializer = new BigEndianNbtSerializer();
    }

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_VAULT, [], function () {
            $this->main->getLogger()->info("§aTable `vaults` has been successfully init");
        });
    }

    public function open(VaultOpenPayload $payload): Generator
    {
        if(isset($this->cached[$payload->username][$payload->number])){
            return $this->cached[$payload->username][$payload->number];
        }

        $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::OPEN_VAULT, $payload->jsonSerialize());

        if(empty($rows)){
            return [];
        }

        $items = [];
        foreach($rows as $row){
            if(! isset($row["items"])){
                continue;
            }

            $items = $this->main->getDatabaseManager()->readItems($this->main->getDatabaseManager()->getBinaryStringParser()->decode($row["items"]), self::TAG_INVENTORY);
        }

        return $items;
    }

    public function close(CloseVaultPayload $payload, array $items = []): Generator
    {
        if (! empty($items) || $items !== []){
            $this->cached[$payload->username][$payload->number] = $items;
            $payload = new CloseVaultPayload(
                $payload->uuid,
                $payload->username,

                $this->main->getDatabaseManager()->getBinaryStringParser()->encode($this->main->getDatabaseManager()->writeItems($items, self::TAG_INVENTORY)),
                $payload->number
            );
        }

        yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::CLOSE_VAULT, $payload->jsonSerialize());
    }
}