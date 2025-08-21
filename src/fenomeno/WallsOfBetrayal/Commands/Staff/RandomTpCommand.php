<?php

namespace fenomeno\WallsOfBetrayal\Commands\Staff;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class RandomTpCommand extends WCommand
{

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (count($sender->getServer()->getOnlinePlayers()) === 1){
            MessagesUtils::sendTo($sender, MessagesIds::SERVER_EMPTY);
        }

        $players = array_filter(
            $sender->getServer()->getOnlinePlayers(),
            fn($player) => $player->getName() !== $sender->getName()
        );

        if (empty($players)) {
            MessagesUtils::sendTo($sender, MessagesIds::NO_OTHER_PLAYERS);
            return;
        }

        $randomPlayer = $players[array_rand($players)];

        $sender->teleport($randomPlayer->getPosition());
        MessagesUtils::sendTo($sender, MessagesIds::TELEPORTED_TO_PLAYER, [ExtraTags::PLAYER => $randomPlayer->getName()]);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::RANDOM_TP);
    }
}