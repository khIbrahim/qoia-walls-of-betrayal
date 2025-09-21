<?php

namespace fenomeno\WallsOfBetrayal\Blocks\Tiles;

use fenomeno\WallsOfBetrayal\Class\FloatingText;
use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyCause;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\block\tile\Spawnable;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\EnchantmentTableParticle;
use pocketmine\world\particle\HeartParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\particle\RedstoneParticle;
use pocketmine\world\Position;

class TotemHealTile extends Spawnable
{

    protected const BANDAGE_TAG       = "Bandage";
    protected const OWNER_TAG         = "Owner";
    protected const KINGDOM_TAG       = "Kingdom";
    protected const CREATION_TIME_TAG = "CreationTime";

    protected const LEVEL_TAG = "Level";
    public const MAX_LEVEL = 5;

    public const HEAL_RANGES = [10, 12, 15, 18, 22];
    public const HEAL_AMOUNTS = [3, 4, 5, 6, 7];
    public const MAX_BANDAGES_PER_LEVEL = [64, 96, 128, 160, 200];
    protected const HEAL_TIMES = [400, 380, 360, 340, 320];

    protected const HEAL_TIME    = 20 * 20;
    public const HEAL_RANGE = 10;
    protected const HEAL_AMOUNT = 3;
    public const MAX_BANDAGES = 64;
    protected const LOYALTY_BASE = 2;
    public const MAX_LIFETIME = 86400 / 2;

    protected const DESTROY_DELAY_AFTER_EXPIRATION = 180;

    private int $bandage = 0;
    private string $owner = "";
    private string $kingdomId = "";
    private int $creationTime = 0;
    private int $lastUsed = 0;
    protected int $healTimer = 0;
    private array $healedPlayers = [];
    private int $level = 1;
    private int $expirationTimestamp = 0;
    private bool $destroyScheduled = false;

    public function setOwner(string $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function setKingdomId(string $kingdomId): static
    {
        $this->kingdomId = $kingdomId;

        return $this;
    }

    public function setCreationTime(int $creationTime): static
    {
        $this->creationTime = $creationTime;

        return $this;
    }

    private FloatingText $floatingText;

    public function heal(): void
    {
        if(! isset($this->floatingText)){
            $this->floatingText = new FloatingText(
                id: "totem_heal:{$this->getBlock()->getTypeId()}",
                position: Position::fromObject($this->getPosition()->add(0, 1.25, 0), $this->getPosition()->getWorld()),
                text: $this->getTotemStatusText()
            );
        }

        if (! $this->isExpired()){
            $this->floatingText->setText($this->getTotemStatusText());
        } else {
            $this->floatingText->setText(MessagesUtils::getMessage(MessagesIds::TOTEM_HEAL_EXPIRED));
        }

        foreach ($this->getPosition()->getWorld()->getPlayers() as $player) {
            if ($player->getPosition()->distance($this->getPosition()) <= 16) {
                $this->floatingText->sendTo($player);
            }
        }

        if ($this->isExpired()) {
            if ($this->expirationTimestamp === 0) {
                $this->expirationTimestamp = time();
            }

            if (! $this->destroyScheduled && (time() - $this->expirationTimestamp) >= self::DESTROY_DELAY_AFTER_EXPIRATION) {
                $this->scheduleDestruction();
            }

            if (! $this->destroyScheduled) {
                $this->spawnExpirationWarningEffects();
            }
            return;
        }

        if (! $this->canHeal()){
            return;
        }

        if ($this->healTimer < 0){
            $this->healedPlayers = [];

            $healRange = self::HEAL_RANGE;
            $pos       = $this->getPosition();
            $entities  = $pos->getWorld()->getNearbyEntities(
                new AxisAlignedBB(
                    $pos->x - $healRange, $pos->y - 2, $pos->z - $healRange,
                    $pos->x + $healRange, $pos->y + 4, $pos->z + $healRange
                )
            );

            $this->spawnBaseEffects();

            foreach ($entities as $entity) {
                if (! ($entity instanceof Player)) {
                    continue;
                }

                $session = Session::get($entity);
                if (! $session->isLoaded() || $session->getKingdom()?->getId() !== ($this->kingdomId ?? "")) {
                    continue;
                }

                if (in_array($entity->getName(), $this->healedPlayers)) {
                    continue;
                }

                $this->healedPlayers[] = $entity;

                $this->applyHealingEffects();
            }

            if (count($this->healedPlayers) > 0) {
                $this->consumeBandage();
                $this->lastUsed = time();
            }

            $this->healTimer = self::HEAL_TIME;
        }

        $this->sync();
        $this->healTimer--;
    }

    protected function applyHealingEffects(): void
    {
        /** @var Player $player */
        foreach ($this->healedPlayers as $player) {
            if($player->getHealth() >= $player->getMaxHealth()){
                continue;
            }

            $player->setHealth(min($player->getHealth() + self::HEAL_AMOUNT, $player->getMaxHealth()));
            $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 100, 0));
            $this->spawnPlayerEffects($player);

            MessagesUtils::sendTo($player, MessagesIds::TOTEM_HEAL_RECEIVED, [
                ExtraTags::HEAL => self::HEAL_AMOUNT
            ]);

            if (isset($this->owner)) {
                $owner = Server::getInstance()->getPlayerExact($this->owner);
                if ($owner !== null && $owner->isOnline() && $owner->getName() !== $player->getName()) {
                    $base = self::LOYALTY_BASE;
                    $timeFactor = max(0.5, min(1.0, (time() - $this->lastUsed) / 3600));
                    $loyaltyAmount = (int)($base * $timeFactor);

                    if ($loyaltyAmount > 0) {
                        Main::getInstance()->getLoyaltyManager()->addLoyalty(
                            $owner,
                            LoyaltyCause::HEAL_ALLY,
                            $loyaltyAmount
                        );
                    }
                }
            }
        }
    }

    protected function spawnPlayerEffects(Player $player): void {
        $pos = $player->getPosition();
        $world = $pos->getWorld();

        for ($i = 0; $i < 8; $i++) {
            $world->addParticle($pos->add(
                (mt_rand(-10, 10) / 10),
                1.5,
                (mt_rand(-10, 10) / 10)
            ), new HeartParticle());
        }

        $pk = PlaySoundPacket::create(
            "random.orb",
            $pos->x, $pos->y, $pos->z,
            1.0,
            1.5
        );
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function getTotemStatusText(): string {
        $levelColor = match(true) {
            $this->level >= 5 => "§c§l",
            $this->level >= 4 => "§6",
            $this->level >= 3 => "§e",
            $this->level >= 2 => "§a",
            default => "§f"
        };

        $statusSymbol = $this->canHeal() ? "§a♦" : "§c♢";
        $healthSymbol = str_repeat("§c❤", $this->getHealAmountForLevel());

        return MessagesUtils::getMessage(MessagesIds::TOTEM_HEAL_FLOATING_TEXT, [
            ExtraTags::COLOR      => $levelColor,
            ExtraTags::LEVEL      => $this->level,
            ExtraTags::STATUS     => $statusSymbol,
            ExtraTags::HEAL_POWER => $healthSymbol,
            ExtraTags::BANDAGE    => $this->bandage,
            ExtraTags::MAX        => $this->getMaxBandagesForLevel(),
            ExtraTags::RANGE      => $this->getHealRangeForLevel(),
            ExtraTags::TIME       => $this->getRemainingHealTime(),
            ExtraTags::LIFETIME => DurationParser::getReadableDuration($this->getRemainingLifetime(), false)
        ]);
    }

    protected function spawnBaseEffects(): void
    {
        $pos = $this->getPosition();
        $world = $pos->getWorld();

        $radius = 1 + ($this->level * 0.5);
        $particleCount = 12 + ($this->level * 3);

        for ($i = 0; $i < $particleCount; $i++) {
            $angle = (360 / $particleCount) * $i;
            $x = cos(deg2rad($angle)) * $radius;
            $z = sin(deg2rad($angle)) * $radius;

            $particle = match($this->level) {
                5 => new HeartParticle(),
                default => new EnchantmentTableParticle()
            };

            $world->addParticle($pos->add($x + 0.5, 0.5, $z + 0.5), $particle);
        }

        $soundName = match($this->level) {
            5 => "beacon.power",
            4 => "beacon.activate",
            3 => "random.orb",
            default => "ambient.soul_sand_valley.mood"
        };

        $pk = PlaySoundPacket::create(
            $soundName,
            $pos->x + 0.5, $pos->y, $pos->z + 0.5,
            0.5 + ($this->level * 0.1),
            1.0 + ($this->level * 0.1)
        );

        foreach ($world->getPlayers() as $p) {
            if ($p->getPosition()->distance($pos) <= $this->getHealRangeForLevel() * 1.5) {
                $p->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    public function getRemainingHealTime()
    {
        return max(0, (int)($this->healTimer / 20));
    }

    public function getKingdomId(): string
    {
        return $this->kingdomId;
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {
    }

    public function readSaveData(CompoundTag $nbt): void
    {
        if(($bandageTag = $nbt->getTag(self::BANDAGE_TAG)) instanceof IntTag){
            $this->bandage = $bandageTag->getValue();
        }
        if(($ownerTag = $nbt->getTag(self::OWNER_TAG)) instanceof StringTag){
            $this->owner = $ownerTag->getValue();
        }
        if(($kingdomTag = $nbt->getTag(self::KINGDOM_TAG)) instanceof IntTag) {
            $this->kingdomId = $kingdomTag->getValue();
        }
        if(($creationTag = $nbt->getTag(self::CREATION_TIME_TAG)) instanceof IntTag) {
            $this->creationTime = $creationTag->getValue();
        } else {
            $this->creationTime = time();
        }
        if(($levelTag = $nbt->getTag(self::LEVEL_TAG)) instanceof IntTag) {
            $this->level = $levelTag->getValue();
        } else {
            $this->level = 1;
        }
        $this->sync();
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setInt(self::BANDAGE_TAG, $this->bandage);
        $nbt->setString(self::OWNER_TAG, $this->owner);
        $nbt->setString(self::KINGDOM_TAG, $this->kingdomId);
        $nbt->setInt(self::CREATION_TIME_TAG, $this->creationTime);
        $nbt->setInt(self::LEVEL_TAG, $this->level);
    }

    public function getBandage(): int
    {
        return $this->bandage;
    }

    public function canHeal(): bool
    {
        return $this->bandage > 0 && ! $this->isExpired();
    }

    protected function consumeBandage(): void
    {
        if ($this->bandage > 0) {
            $this->bandage--;
            $this->sync();
        }
    }

    public function addBandage(int $count = 1): bool {
        if ($this->bandage + $count > self::MAX_BANDAGES) {
            return false;
        }
        $this->bandage += $count;
        $this->sync();
        return true;
    }
    public function isExpired(): bool {
        return (time() - $this->creationTime) > self::MAX_LIFETIME;
    }

    public function getRemainingLifetime(): int {
        return max(0, self::MAX_LIFETIME - (time() - $this->creationTime));
    }

    protected function sync(bool $saveNbt = false): void {
        if ($saveNbt){
            $this->saveNBT();
        }

        $this->position->getWorld()->loadChunk(
            $this->position->getFloorX() >> 4,
            $this->position->getFloorZ() >> 4
        );

        if (!$this->isExpired()) {
            if ($this->bandage > 0) {
                $this->spawnBaseEffects();
            } else {
                $this->spawnInactiveEffects();
            }
        } else if (!$this->destroyScheduled) {
            $this->spawnExpirationWarningEffects();
        }
    }

    protected function spawnInactiveEffects(): void
    {
        $pos = $this->getPosition();
        $world = $pos->getWorld();

        $pk = PlaySoundPacket::create(
            "block.fire.extinguish",
            $pos->x, $pos->y, $pos->z,
            1.0,
            1.0
        );

        foreach ($world->getPlayers() as $p) {
            if ($p->getPosition()->distance($pos) <= self::HEAL_RANGE * 1.5) {
                $p->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    protected function onBlockDestroyedHook(): void
    {
        if (isset($this->floatingText)) {
            foreach ($this->getPosition()->getWorld()->getPlayers() as $player) {
                $this->floatingText->hideFor($player);
            }
        }

        parent::onBlockDestroyedHook();
    }

    public function getLevel(): int {
        return $this->level;
    }

    public function getMaxBandagesForLevel(): int {
        return self::MAX_BANDAGES_PER_LEVEL[$this->level - 1];
    }

    public function getHealRangeForLevel(): int {
        return self::HEAL_RANGES[$this->level - 1];
    }

    public function getHealAmountForLevel(): int {
        return self::HEAL_AMOUNTS[$this->level - 1];
    }

    public function upgradeLevel(): bool {
        if ($this->level >= self::MAX_LEVEL) {
            return false;
        }
        $this->level++;
        $this->sync();
        return true;
    }

    public function isMaxLevel(): bool {
        return $this->level >= self::MAX_LEVEL;
    }

    protected function spawnExpirationWarningEffects(): void {
        $timeLeft = self::DESTROY_DELAY_AFTER_EXPIRATION - (time() - $this->expirationTimestamp);
        if ($timeLeft <= 0) return;

        $pos   = $this->getPosition();
        $world = $pos->getWorld();

        for ($i = 0; $i < 4; $i++) {
            $angle = ($i / 4) * M_PI * 2;
            $x = cos($angle);
            $z = sin($angle);
            $world->addParticle($pos->add($x + 0.5, 0.5, $z + 0.5), new RedstoneParticle());
        }

        if ($timeLeft % 30 === 0) {
            $pk = PlaySoundPacket::create(
                "random.fizz",
                $pos->x + 0.5, $pos->y, $pos->z + 0.5,
                1.0,
                0.5
            );

            foreach ($world->getPlayers() as $p) {
                if ($p->getPosition()->distance($pos) <= 16) {
                    $p->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }
    }

    private function scheduleDestruction(): void {
        $this->destroyScheduled = true;

        $pos = $this->getPosition();
        $world = $pos->getWorld();

        $world->addParticle($pos->add(0.5, 0.5, 0.5), new HugeExplodeParticle());

        $pk = PlaySoundPacket::create(
            "random.explode",
            $pos->x + 0.5, $pos->y, $pos->z + 0.5,
            1.0,
            1.0
        );

        foreach ($world->getPlayers() as $p) {
            if ($p->getPosition()->distance($pos) <= 16) {
                $p->getNetworkSession()->sendDataPacket($pk);
                MessagesUtils::sendTo($p, MessagesIds::TOTEM_DESTROYED_EXPIRED);
            }
        }

        $world->useBreakOn($pos);
        $this->close();
    }
}
