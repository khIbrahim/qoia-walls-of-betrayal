<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use Throwable;

class SetLobbyCommand extends WCommand
{

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        Await::g2c(
            $this->main->getServerManager()->getLobbyManager()->updateLobbyLoc($sender->getLocation()),
            function (Location $newLocation) use ($sender) {
                MessagesUtils::sendTo($sender, MessagesIds::SET_LOBBY_SUCCESS);

                $this->main->getLogger()->info($sender->getName() . " updated lobby location to " . $newLocation->__toString());
            },
            fn (Throwable $e) => Utils::onFailure($e, $sender, "Failed to update lobby location by {$sender->getName()}: " . $e->getMessage())
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SET_LOBBY);
    }
}