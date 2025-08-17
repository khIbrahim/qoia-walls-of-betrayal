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
        $database->executeGeneric(Statements::INIT_VAULT);
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

            $items = $this->read($row["items"]);
        }

        return $items;
    }

    public function read(?string $data) : array{
        if ($data === "" || $data === null) {
            return [];
        }

        $contents = [];
        $inventoryTag = $this->nbtSerializer->read(zlib_decode($data))->mustGetCompoundTag()->getListTag(self::TAG_INVENTORY);
        /** @var CompoundTag $tag */
        foreach($inventoryTag as $tag){
            $contents[$tag->getByte("Slot")] = Item::nbtDeserialize($tag);
        }

        return $contents;
    }

    public function write(array $c) : string{
        $contents = [];
        foreach($c as $slot => $item){
            $contents[] = $item->nbtSerialize($slot);
        }

        return zlib_encode($this->nbtSerializer->write(new TreeRoot(CompoundTag::create()
            ->setTag(self::TAG_INVENTORY, new ListTag($contents, NBT::TAG_Compound))
        )), ZLIB_ENCODING_GZIP);
    }

    public function close(CloseVaultPayload $payload, array $items = []): Generator
    {
        if (! empty($items) || $items !== []){
            $this->cached[$payload->username][$payload->number] = $items;
            $payload = new CloseVaultPayload(
                $payload->uuid,
                $payload->username,
                $this->write($items),
                $payload->number
            );
        }

        yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::CLOSE_VAULT, $payload->jsonSerialize());
    }
}