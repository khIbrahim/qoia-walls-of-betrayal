<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class FeedCommand extends WCommand
{

    private const PLAYER_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = ($args[self::PLAYER_ARGUMENT] ?? $sender->getName());
        $player     = $sender->getServer()->getPlayerExact($playerName);
        if ($player === null) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        $player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
        $player->getHungerManager()->setSaturation(20.0);
        if ($sender === $player) {
            MessagesUtils::sendTo($sender, MessagesIds::HUNGER_RESTORED);
        } else {
            MessagesUtils::sendTo($sender, MessagesIds::HUNGER_RESTORED_FOR_PLAYER, [ExtraTags::PLAYER => $player->getName()]);
            MessagesUtils::sendTo($player, MessagesIds::HUNGER_RESTORED);
        }

        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
            soundName: 'entity.player.burp',
            x: $player->getPosition()->x,
            y: $player->getPosition()->y,
            z: $player->getPosition()->z,
            volume: 1.0,
            pitch: 1.0,
        ));
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::FEED);
    }
}