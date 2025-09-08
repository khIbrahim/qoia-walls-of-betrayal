<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;

class KingdomRallySubCommand extends WSubCommand
{

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = Session::get($sender);
        if (!$session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [ExtraTags::PLAYER => $sender->getName()]);
            return;
        }

        $kingdom = $session->getKingdom();
        if ($kingdom === null) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_IN_KINGDOM);
            return;
        }

        $rallyPoint = $kingdom->getRallyPoint();
        if (!$rallyPoint) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_RALLY_NO_RALLY);
            return;
        }

        $sender->teleport($rallyPoint, $rallyPoint->yaw, $rallyPoint->pitch);
        $sender->broadcastSound(new EndermanTeleportSound());
        $sender->getWorld()->addParticle($sender->getPosition(), new EndermanTeleportParticle());
        MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_RALLY_TELEPORT);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_RALLY);
    }
}