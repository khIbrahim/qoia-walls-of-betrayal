<?php

namespace fenomeno\WallsOfBetrayal\Entities\Types;

use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use Throwable;

class NpcEntity extends Human
{

    public const DEFAULT_NPC_COOLDOWN = 1;

    protected const COMMAND_TAG      = 'NpcCommand';
    protected const NPC_ID_TAG       = 'NpcId';
    protected const NPC_COOLDOWN_TAG = 'NpcCooldown';

    protected float $gravity = 0.0;
    private string $command;
    private string $npcId;
    private int $cooldown = self::DEFAULT_NPC_COOLDOWN;

    public function getNpcId(): string
    {
        return $this->npcId;
    }

    public function getStoredCommand(): string
    {
        return $this->command;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible();
        $this->setScale(1.0);

        if ($nbt->getTag(self::COMMAND_TAG) !== null && $nbt->getTag(self::NPC_ID_TAG) !== null){
            $this->cooldown = $nbt->getTag(self::NPC_COOLDOWN_TAG) !== null ? $nbt->getInt(self::NPC_COOLDOWN_TAG) : self::DEFAULT_NPC_COOLDOWN;
            $this->command  = $nbt->getString(self::COMMAND_TAG);
            $this->npcId    = $nbt->getString(self::NPC_ID_TAG);

            Await::g2c(
                Main::getInstance()->getNpcManager()->add($this),
                function (self $npc){Main::getInstance()->getLogger()->debug($npc->getNpcId() . ' added to manager from init entity');},
                function (Throwable $e){Utils::onFailure($e, null, "Failed to add npc $this->npcId to the manager from the init entity:" . $e->getMessage());}
            );
        }
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        if (isset($this->npcId)){
            $nbt->setString(self::NPC_ID_TAG, $this->npcId);
        }

        if (isset($this->command)){
            $nbt->setString(self::COMMAND_TAG, $this->command);
        }

        if ($this->cooldown !== self::DEFAULT_NPC_COOLDOWN){
            $nbt->setInt(self::NPC_COOLDOWN_TAG, $this->cooldown);
        }

        return $nbt;
    }

    protected function move(float $dx, float $dy, float $dz): void
    {

    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent){
            $player = $source->getDamager();
            if ($player instanceof Player){
                if ($player->getInventory()->getItemInHand()->getTypeId() === VanillaItems::GOLDEN_CARROT()->getTypeId() && $player->hasPermission(PermissionIds::NPC_REMOVE)){
                    $this->despawn($player);
                    return;
                }

                $this->executeCommandFor($player);
            }
        }
    }

    public function despawn(?Player $player = null): void
    {
        if(isset($this->npcId)){
            Await::g2c(
                Main::getInstance()->getNpcManager()->remove($this->npcId),
                function (string $id) use ($player) {
                    if ($player !== null){
                        MessagesUtils::sendTo($player, MessagesIds::NPC_REMOVED, [ExtraTags::NPC => $id]);
                    }
                },
                fn(Throwable $e) => Utils::onFailure($e, $player, "Failed to remove npc $this->npcId with attack: " . $e->getMessage())
            );

            return;
        }

        if(! $this->isFlaggedForDespawn()){
            $this->flagForDespawn();
        }
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        $this->executeCommandFor($player);
        return true;
    }

    public function executeCommandFor(Player $player): void
    {
        if (isset($this->command, $this->npcId)){
            if (Main::getInstance()->getCooldownManager()->isOnCooldown($this->getNpcId(), $player->getName())){
                MessagesUtils::sendTo($player, MessagesIds::NPC_COOLDOWN, [ExtraTags::TIME => Main::getInstance()->getCooldownManager()->getCooldownRemaining($this->getNpcId(), $player->getName())]);
                return;
            }

            if($player->getServer()->dispatchCommand($player, $this->command)){
                Main::getInstance()->getLogger()->debug("Npc : " . $this->npcId . " successfully executed command for " . $player->getName());
                Main::getInstance()->getCooldownManager()->setCooldown($this->getNpcId(), $player->getName(), $this->cooldown
                );
            } else {
                Main::getInstance()->getLogger()->error("Npc : " . $this->npcId . " failed to execute command for " . $player->getName());
            }
        } else {
            MessagesUtils::sendTo($player, MessagesIds::NPC_NOT_SET);
        }
    }

    public static function make(Location $location, Skin $skin, string $id, string $command, string $name = "Wob NPC", int $cooldown = self::DEFAULT_NPC_COOLDOWN): self
    {
        $npc = new self($location, $skin);
        $npc->setNameTag(str_replace('\n', "\n", $name));

        $npc->setNpcId($id)
            ->setCommand($command)
            ->setCooldown($cooldown);

        return $npc;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function setNpcId(string $npcId): self
    {
        $this->npcId = $npcId;

        return $this;
    }

    public function setCooldown(int $cooldown): self
    {
        $this->cooldown = $cooldown;

        return $this;
    }

    public function getCooldown(): int
    {
        return $this->cooldown;
    }

    public function toArray(): array
    {
        return [
            "id"            => $this->npcId ?? uniqid("npc"),
            "name"          => $this->getNameTag(),
            "command"       => $this->getStoredCommand(),
            "cooldown"      => $this->getCooldown(),
            "world"         => $this->getLocation()->getWorld()->getFolderName(),
            "x"             => $this->getLocation()->getX(),
            "y"             => $this->getLocation()->getY(),
            "z"             => $this->getLocation()->getZ(),
            "yaw"           => $this->getLocation()->yaw,
            "pitch"         => $this->getLocation()->pitch,
            "skin"          => base64_encode($this->getSkin()->getSkinData()),
            "skin_id"       => $this->getSkin()->getSkinId(),
            "cape"          => base64_encode($this->getSkin()->getCapeData()),
            "geometry_name" => $this->getSkin()->getGeometryName(),
            "geometry_data" => base64_encode($this->getSkin()->getGeometryData()),
        ];
    }

}