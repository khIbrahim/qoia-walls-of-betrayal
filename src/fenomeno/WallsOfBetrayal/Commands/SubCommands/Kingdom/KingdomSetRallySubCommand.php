<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Constants\CooldownTypes;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class KingdomSetRallySubCommand extends WSubCommand
{

    private const COOLDOWN_DURATION = 60 * 60 * 24;

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

        if ($this->main->getCooldownManager()->isOnCooldown(CooldownTypes::KINGDOM_RALLY, $kingdom->getId())) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_RALLY_COOLDOWN, [
                ExtraTags::TIME => $this->main->getCooldownManager()->getCooldownRemaining(CooldownTypes::KINGDOM_RALLY, $kingdom->getId(), true),
            ]);
            return;
        }

        Await::f2c(function () use ($sender, $kingdom) {
            try {
                yield from $kingdom->setRallyPoint($sender->getLocation());

                $this->main->getCooldownManager()->setCooldown(CooldownTypes::KINGDOM_RALLY, $kingdom->getId(), self::COOLDOWN_DURATION);
                $kingdom->broadcastMessage(MessagesIds::KINGDOMS_RALLY_SUCCESS, [ExtraTags::PLAYER => $sender->getName()]);
            } catch (Throwable $e) {
                Utils::onFailure($e, $sender, "Failed to update rally point for kingdom $kingdom->id by {$sender->getName()}: " . $e->getMessage());
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SET_RALLY);
    }
}