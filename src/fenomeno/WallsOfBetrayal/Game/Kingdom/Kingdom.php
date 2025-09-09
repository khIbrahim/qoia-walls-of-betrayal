<?php
namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomBase;
use fenomeno\WallsOfBetrayal\Class\KingdomData;
use fenomeno\WallsOfBetrayal\Commands\Arguments\BorderArgument;
use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomDataFilterArgument;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\ContributeKingdomPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\KingdomRallyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\UpdateKingdomSpawnPayload;
use fenomeno\WallsOfBetrayal\DTO\KingdomEnchantment;
use fenomeno\WallsOfBetrayal\Events\Kingdom\PlayerEnterKingdomBaseEvent;
use fenomeno\WallsOfBetrayal\Events\Kingdom\PlayerQuitKingdomBaseEvent;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\InvalidBorderException;
use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\EnchantUtils;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\PositionParser;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use InvalidArgumentException;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\sound\PopSound;
use pocketmine\world\sound\Sound;
use Throwable;

class Kingdom
{

    private KingdomData $kingdomData;
    private KingdomBase $base;

    public function __construct(
        public string $id,
        public string $displayName,
        public string $color,
        public string $description,
        public ?Item $item = null,
        public array $kits = [],
        public array $abilities = [],
        public string $portalId = "",
        public array $enchantments = [],
    ){
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->tick()), 1);
    }

    public function broadcastMessage(string $message, array $extraTags = [], ?string $default = null): void
    {
        foreach ($this->getOnlineMembers() as $member){
            MessagesUtils::sendTo($member, $message, $extraTags, $default);
        }
    }

    public function broadcastSound(Sound $sound): void
    {
        foreach ($this->getOnlineMembers() as $player){
            $player->broadcastSound($sound);
        }
    }

    /** @return Player[] */
    public function getOnlineMembers(): array
    {
        $members = [];

        foreach (Server::getInstance()->getOnlinePlayers() as $player){
            $session = Session::get($player);
            if ($session->isLoaded() && $session->getKingdom() !== null && $session->getKingdom()->id === $this->id){
                $members[] = $player;
            }
        }

        return $members;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /** @return Kit[] */
    public function getKits(): array
    {
        return $this->kits;
    }

    public function setKingdomData(KingdomData $kingdomData): void
    {
        $this->kingdomData = $kingdomData;
    }

    /** @return KingdomEnchantment[] */
    public function getEnchantments(): array
    {
        return $this->enchantments;
    }

    public function applyEnchantmentToPlayer(Player $player, KingdomEnchantment $enchantment): void
    {
        if ($player->getXpManager()->getXpLevel() < $enchantment->cost){
            MessagesUtils::sendTo($player, MessagesIds::ENCHANTING_TABLE_NOT_ENOUGH_XP, [
                ExtraTags::COST => $enchantment->cost
            ]);
            return;
        }

        $item = clone $player->getInventory()->getItemInHand();
        if ($item->isNull()) {
            MessagesUtils::sendTo($player, MessagesIds::NO_ITEM_IN_HAND);
            return;
        }

        if (! AvailableEnchantmentRegistry::getInstance()->isAvailableForItem($enchantment->getEnchantment(), $item)){
            MessagesUtils::sendTo($player, MessagesIds::ENCHANTMENT_NOT_AVAILABLE, [
                ExtraTags::ENCHANTMENT => EnchantUtils::getEnchantmentName($enchantment->getEnchantment()),
                ExtraTags::ITEM        => $item->getCustomName()
            ]);
            return;
        }

        $enchantmentInstance = $enchantment->getEnchantmentInstance();
        $item->addEnchantment($enchantmentInstance);

        $player->getXpManager()->subtractXpLevels($enchantment->cost);
        $player->getInventory()->setItemInHand($item);
        MessagesUtils::sendTo($player, MessagesIds::ENCHANTING_TABLE_SUCCESS, [
            ExtraTags::ENCHANTMENT => EnchantUtils::getEnchantmentName($enchantment->getEnchantment()),
            ExtraTags::ITEM        => $item->getCustomName()
        ]);
        $player->broadcastSound(new PopSound());
    }

    public function addDeath(): void
    {
        Await::g2c(
            Main::getInstance()->getDatabaseManager()->getKingdomRepository()->addDeath(new IdPayload($this->id)),
            fn() => $this->kingdomData->deaths++,
            fn(Throwable $e) => Main::getInstance()->getLogger()->error("Failed to add death for kingdom $this->id: " . $e->getMessage())
        );
    }

    public function addKill(): void
    {
        Await::g2c(
            Main::getInstance()->getDatabaseManager()->getKingdomRepository()->addKill(new IdPayload($this->id)),
            fn() => $this->kingdomData->deaths++,
            fn(Throwable $e) => Main::getInstance()->getLogger()->error("Failed to add kill for kingdom $this->id: " . $e->getMessage())
        );
    }

    public function getSpawn(): null|Location|Position
    {
        return $this->kingdomData->spawn;
    }

    public function updateSpawn(null|Position|Location $location): Generator
    {
        yield from Main::getInstance()->getDatabaseManager()->getKingdomRepository()->updateSpawn(new UpdateKingdomSpawnPayload($this->id, PositionParser::toArray($location)));

        $this->kingdomData->spawn = $location;
        return $location;
    }

    public function getKingdomData(): KingdomData
    {
        return $this->kingdomData;
    }

    public function contribute(int $amount, string $type): Generator
    {
        yield from Main::getInstance()->getDatabaseManager()->getKingdomRepository()->contribute(new ContributeKingdomPayload($this->id, $type, $amount));

        switch ($type) {
            case KingdomDataFilterArgument::XP:
                $this->kingdomData->xp += $amount;
                break;
            case KingdomDataFilterArgument::BALANCE:
                $this->kingdomData->balance += $amount;
                break;
        }
    }

    public function getColor(): Color
    {
        return match ($this->id) {
            default => new Color(255, 0, 0),
            'thragmar' => new Color(0, 0, 255)
        };
    }

    public function setRallyPoint(Location $location): Generator
    {
        yield from Main::getInstance()->getDatabaseManager()->getKingdomRepository()->setRally(new KingdomRallyPayload(
            id: $this->id,
            rallyPoint: PositionParser::toArray($location)
        ));

        $this->kingdomData->rallyPoint = $location;
    }

    public function getRallyPoint(): ?Location
    {
        return $this->kingdomData->rallyPoint;
    }

    public function getBalance(): int
    {
        return $this->kingdomData->balance;
    }

    public function isExcluded(string $uuid): bool
    {
        return Main::getInstance()->getKingdomManager()->isPlayerSanctioned($this->id, $uuid);
    }

    public function getBase(): KingdomBase
    {
        return $this->base ??= new KingdomBase(AxisAlignedBB::one(), Main::getInstance()->getServerManager()->getKingdomsWorld());
    }

    public function setBase(KingdomBase $base): void
    {
        $this->base = $base;
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvalidBorderException
     */
    public function updateBorders(string $type, Location $location): Generator
    {
        if(! isset(BorderArgument::$VALUES[$type])) {
            throw new InvalidArgumentException("Border type must be one of: " . implode(", ", BorderArgument::$VALUES));
        }

        $borders = $this->getBase()->borders;
        $this->getBase()->world = $location->getWorld();

        $x = $location->getX();
        $y = $location->getY();
        $z = $location->getZ();
        if ($type === BorderArgument::MIN){
            if (! Utils::isAABBOne($borders) && ($x > $borders->maxX || $y > $borders->maxY || $z > $borders->maxZ)){
                throw new InvalidBorderException("Minimum border cannot be greater than maximum border");
            }

            $borders->minX = (float) $location->getX();
            $borders->minY = (float) $location->getY();
            $borders->minZ = (float) $location->getZ();
        } else {
            if (! Utils::isAABBOne($borders) && ($x < $borders->minX || $y < $borders->minY || $z < $borders->minZ)){
                throw new InvalidBorderException("Maximum border cannot be less than minimum border");
            }

            $borders->maxX = (float) $location->getX();
            $borders->maxY = (float) $location->getY();
            $borders->maxZ = (float) $location->getZ();
        }

        yield from Main::getInstance()->getDatabaseManager()->getKingdomRepository()->updateKingdomBorders($this->id, $this->getBase()->toArray());
    }

    private function isBaseDefined(): bool
    {
        return isset($this->base) && $this->base->isDefined();
    }

    private function tick(): void
    {
        if (! $this->isBaseDefined()) {
            return;
        }

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $position  = $player->getPosition();
            $isInside  = $this->base->contains($position);
            $wasInside = $this->base->isPlayerInBase($player);

            if ($isInside === $wasInside) {
                continue;
            }

            if ($isInside) {
                $ev = new PlayerEnterKingdomBaseEvent($this, $player);
                $ev->call();

                if ($ev->isCancelled()) {
                    $this->teleportPlayerAway($player, $position);
                    MessagesUtils::sendTo($player, MessagesIds::KINGDOM_BASE_ENTER_CANCELLED, [
                        ExtraTags::KINGDOM => $this->getDisplayName()
                    ]);
                    continue;
                }

                $this->base->addPlayerInBase($player);
            } else {
                $ev = new PlayerQuitKingdomBaseEvent($this, $player);
                $ev->call();

                if ($ev->isCancelled()) {
                    $this->teleportPlayerAway($player, $position);
                    MessagesUtils::sendTo($player, MessagesIds::KINGDOM_BASE_QUIT_CANCELLED, [
                        ExtraTags::KINGDOM => $this->getDisplayName()
                    ]);
                    continue;
                }

                $this->base->removePlayerFromBase($player);
            }
        }
    }

    private function teleportPlayerAway(Player $player, Position $position): void
    {
        $center = $this->base->getCenter();
        $isInside = $this->base->borders->isVectorInside($position);

        $direction = $isInside
            ? $position->subtractVector($center)->normalize() // inside
            : $center->subtractVector($position)->normalize()->multiply(-1); // outside

        $force = 1.5;
        $knockbackX = $direction->x * $force;
        $knockbackY = 0.4;
        $knockbackZ = $direction->z * $force;

        $player->setMotion(new Vector3($knockbackX, $knockbackY, $knockbackZ));

        MessagesUtils::sendTo($player, $isInside
            ? MessagesIds::KINGDOM_BASE_QUIT_CANCELLED
            : MessagesIds::KINGDOM_BASE_ENTER_CANCELLED, [
            ExtraTags::KINGDOM => $this->getDisplayName()
        ]);
    }

}