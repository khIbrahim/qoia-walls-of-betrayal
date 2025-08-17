<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Blocks\BlockManager;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Entities\EntityManager;
use fenomeno\WallsOfBetrayal\Inventory\SpawnersInventory;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class SpawnerCommand extends WCommand
{

    public const PLAYER_ARGUMENT = "player";
    public const SPAWNER_ARGUMENT = "spawner";
    public const COUNT_ARGUMENT = "count";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, true));
        $this->registerArgument(1, new RawStringArgument(self::SPAWNER_ARGUMENT, true));
        $this->registerArgument(2, new IntegerArgument(self::COUNT_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if(! isset($args[self::PLAYER_ARGUMENT])) {
            if(! $sender instanceof Player){
                MessagesUtils::sendTo($sender, MessagesIds::NOT_PLAYER);
                return;
            }

            (new SpawnersInventory())->send($sender);
            return;
        }

        $playerName = $args[self::PLAYER_ARGUMENT];
        $player     = $sender->getServer()->getPlayerByPrefix($playerName);
        if(! $player instanceof Player){
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        if (! isset($args[self::COUNT_ARGUMENT], $args[self::SPAWNER_ARGUMENT])) {
            $sender->sendMessage($this->getUsageMessage());
            return;
        }

        $spawnerId  = (string) $args[self::SPAWNER_ARGUMENT];
        $entityInfo = EntityManager::getInstance()->getEntityInfoByName($spawnerId);
        if ($entityInfo === null) {
            MessagesUtils::sendTo($sender, MessagesIds::SPAWNER_NOT_FOUND, [ExtraTags::SPAWNER => $spawnerId]);
            return;
        }

        $count = (int) $args[self::COUNT_ARGUMENT];
        if ($count <= 0 || $count > 64) {
            MessagesUtils::sendTo($sender, MessagesIds::INVALID_NUMBER, [ExtraTags::NUMBER => $count, ExtraTags::MAX => 64, ExtraTags::MIN => 1]);
            return;
        }

        $item = BlockManager::getInstance()->getMobSpawnerItem($entityInfo->getLegacyId(), $count);
        if ($player->getInventory()->canAddItem($item)) {
            $player->getInventory()->addItem($item);
        } else {
            $player->getWorld()->dropItem($player->getPosition(), $item);
        }
        MessagesUtils::sendTo($sender, MessagesIds::SPAWNER_ADDED, [
            ExtraTags::PLAYER  => $player->getName(),
            ExtraTags::SPAWNER => $item->getCustomName(),
            ExtraTags::NUMBER  => $count,
        ]);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SPAWNER);
    }
}