<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\SeeKingdomBordersUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class KingdomSeeBordersSubCommand extends WSubCommand
{

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        Await::g2c(
            SeeKingdomBordersUtils::toggleBorderView($sender),
            function (bool $isNowViewing) use ($sender) {
                MessagesUtils::sendTo($sender, $isNowViewing ? MessagesIds::NOW_VIEWING_KINGDOM_BORDERS : MessagesIds::NO_LONGER_VIEWING_KINGDOM_BORDERS, [
                    ExtraTags::KINGDOM => implode(", ", array_map(fn($k) => $k->getDisplayName(), $this->main->getKingdomManager()->getKingdoms()))
                ]);
            },
            fn (Throwable $e) => Utils::onFailure($e, $sender, "Failed to toggle kingdom borders view.")
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SEE_BORDERS);
    }
}