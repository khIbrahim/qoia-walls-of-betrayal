<?php

namespace fenomeno\WallsOfBetrayal\Blocks\Types;

use fenomeno\WallsOfBetrayal\Blocks\Tiles\TotemHealTile;
use fenomeno\WallsOfBetrayal\Items\Items\Bandage;
use fenomeno\WallsOfBetrayal\Items\WobItems;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo as Info;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\BlockTransaction;
use pocketmine\world\particle\EnchantmentTableParticle;
use pocketmine\world\Position;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\BlockIdentifier;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\Builder\BlockPermutationBuilder;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\Component\OnInteractComponent;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\Component\Sub\MaterialSubComponent;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\Enum\RenderMethodEnum;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\Enum\TargetMaterialEnum;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\Permutation\Permutations;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\Trait\PlacementDirectionTrait;
use SenseiTarzan\SymplyPlugin\Behavior\Blocks\TransparentPermutation;

class TotemHealBlock extends TransparentPermutation
{
    use HorizontalFacingTrait;

    public const IDENTIFIER = 'wob:totem_heal';

    public function __construct() {
        parent::__construct(new BlockIdentifier(self::IDENTIFIER, BlockTypeIds::newId(), TotemHealTile::class), "Totem Of Healing", new Info(BreakInfo::axe(2.0)));
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool {
        // Vérifie si on tente d'améliorer un totem existant
        if ($blockClicked instanceof self) {
            $tile = $blockClicked->getPosition()->getWorld()->getTile($blockClicked->getPosition());
            if ($tile instanceof TotemHealTile && $player !== null) {
                $session = Session::get($player);
                if (!$session->isLoaded()) {
                    MessagesUtils::sendTo($player, MessagesIds::SESSION_NOT_LOADED);
                    return false;
                }

                if ($tile->isMaxLevel()) {
                    MessagesUtils::sendTo($player, MessagesIds::TOTEM_MAX_LEVEL);
                    return false;
                }

                $kingdom = $session->getKingdom();
                if ($kingdom === null || $kingdom->getId() !== $tile->getKingdomId()) {
                    MessagesUtils::sendTo($player, MessagesIds::TOTEM_WRONG_KINGDOM);
                    return false;
                }

                $oldLevel = $tile->getLevel();
                $tile->upgradeLevel();

                $this->spawnUpgradeEffects($blockClicked->getPosition());

                MessagesUtils::sendTo($player, MessagesIds::TOTEM_UPGRADED, [
                    ExtraTags::OLD_LEVEL => $oldLevel,
                    ExtraTags::NEW_LEVEL => $tile->getLevel()
                ]);

                return false;
            }
        }

        $placed = parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);

        if ($placed && $player !== null) {
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($player, $tx){
                foreach ($tx->getBlocks() as [$x, $y, $z, $block]) {
                    $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
                    if ($tile instanceof TotemHealTile) {
                        $session = Session::get($player);
                        if (! $session->isLoaded()) {
                            MessagesUtils::sendTo($player, MessagesIds::SESSION_NOT_LOADED);
                            return;
                        }

                        $kingdom = $session->getKingdom();
                        if ($kingdom === null) {
                            MessagesUtils::sendTo($player, MessagesIds::UNKNOWN_KINGDOM);
                            return;
                        }

                        /** @var TotemHealTile $tile */
                        $tile->setOwner($player->getName())
                            ->setKingdomId($kingdom->getId())
                            ->setCreationTime(time() + TotemHealTile::MAX_LIFETIME);
                    }
                }
            }), 1);
        }

        return $placed;
    }

    protected function spawnUpgradeEffects(Position $position): void {
        $world = $position->getWorld();

        for ($y = 0; $y < 2; $y += 0.1) {
            $angle = $y * 20;
            $x = cos(deg2rad($angle)) * (2 - $y);
            $z = sin(deg2rad($angle)) * (2 - $y);
            $world->addParticle($position->add($x + 0.5, $y, $z + 0.5), new EnchantmentTableParticle());
        }

        $pk = PlaySoundPacket::create(
            "random.levelup",
            $position->x + 0.5,
            $position->y,
            $position->z + 0.5,
            1.0,
            1.0
        );

        foreach ($world->getPlayers() as $p) {
            if ($p->getPosition()->distance($position) <= 16) {
                $p->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []): bool {
        $tile = $this->position->getWorld()->getTile($this->position);
        if (!($tile instanceof TotemHealTile)) return false;

        if ($item instanceof Bandage) {
            $count = $item->getCount();
            if ($tile->addBandage($count)) {
                $player->getInventory()->removeItem($item);
                $bandage = $tile->getBandage();
                MessagesUtils::sendTo($player, MessagesIds::TOTEM_HEAL_FILL, [
                    ExtraTags::BANDAGE => $bandage,
                    ExtraTags::MAX => TotemHealTile::MAX_BANDAGES
                ]);
            } else {
                MessagesUtils::sendTo($player, MessagesIds::TOTEM_HEAL_FULL, [
                    ExtraTags::MAX => TotemHealTile::MAX_BANDAGES
                ]);
            }
            return true;
        }

        $player->sendMessage($tile->getTotemStatusText());
        return true;
    }

    public function onScheduledUpdate(): void {
        $tile = $this->position->getWorld()->getTile($this->position);
        if (! ($tile instanceof TotemHealTile) || $tile->isClosed()) return;

        $tile->heal();

        $this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 1);
    }

    public function getDrops(Item $item): array {
        $tile = $this->position->getWorld()->getTile($this->position);
        if (! ($tile instanceof TotemHealTile) || $tile->isClosed()) {
            return [
                $this->asItem(),
            ];
        } else {
            return [
                $this->asItem(), WobItems::BANDAGE()->setCount($tile->getBandage())
            ];
        }
    }

    public function asItem(): Item
    {
        $item = parent::asItem();
        $item->setCustomName("§r§aTotem of Healing");
        $item->setLore([
            "§r",
            "§r§7Place this totem to heal nearby allies.",
            "§r§7Requires bandages to function.",
            "§r§7Can be upgraded up to level " . TotemHealTile::MAX_LEVEL . "!",
            "§r",
            "§r§7Stats per level:",
            "§r§7- Heal Range: " . implode("➜", TotemHealTile::HEAL_RANGES) . " blocks",
            "§r§7- Heal Amount: " . implode("➜", TotemHealTile::HEAL_AMOUNTS) . " HP",
            "§r§7- Max Bandages: " . implode("➜", TotemHealTile::MAX_BANDAGES_PER_LEVEL),
            "§r",
            "§r§7A symbol of hope and resilience for your kingdom."
        ]);
        return $item;
    }

    public function getBlockBuilder(): BlockPermutationBuilder {
        return parent::getBlockBuilder()
            ->setGeometry("geometry.totem_heal")
            ->addComponent(new OnInteractComponent())
            ->addTrait(new PlacementDirectionTrait(true))
            ->setCollisionBox(new Vector3(-8,0,-8), new Vector3(16, 16, 16))
            ->setSelectionBox(new Vector3(-8,0,-8), new Vector3(16, 16, 16))
            ->setMaterialInstance(materials: [
                new MaterialSubComponent(TargetMaterialEnum::ALL, "totem_heal", RenderMethodEnum::ALPHA_TEST)
            ])
            ->addPermutation(Permutations::create()
                ->setCondition("q.block_state('" . BlockStateNames::MC_CARDINAL_DIRECTION . "') == 'south'")
                ->setTransformationComponent(new Vector3(0, 180, 0), new Vector3(1, 1, 1), new Vector3(0, 0, 0)))
            ->addPermutation(Permutations::create()
                ->setCondition("q.block_state('" . BlockStateNames::MC_CARDINAL_DIRECTION . "') == 'north'")
                ->setTransformationComponent(new Vector3(0, 270, 0), new Vector3(1, 1, 1), new Vector3(0, 0, 0)))
            ->addPermutation(Permutations::create()
                ->setCondition("q.block_state('" . BlockStateNames::MC_CARDINAL_DIRECTION . "') == 'east'")
                ->setTransformationComponent(new Vector3(0, 90, 0), new Vector3(1, 1, 1), new Vector3(0, 0, 0)))
            ->addPermutation(Permutations::create()
                ->setCondition("q.block_state('" . BlockStateNames::MC_CARDINAL_DIRECTION . "') == 'west'")
                ->setTransformationComponent(new Vector3(0, -90, 0), new Vector3(1, 1, 1), new Vector3(0, 0, 0)));
    }

    public function serializeState(BlockStateWriter $writer): void {
        $writer->writeCardinalHorizontalFacing($this->getFacing());
    }

    public function deserializeState(BlockStateReader $reader): void {
        $this->setFacing($reader->readCardinalHorizontalFacing());
    }
}

