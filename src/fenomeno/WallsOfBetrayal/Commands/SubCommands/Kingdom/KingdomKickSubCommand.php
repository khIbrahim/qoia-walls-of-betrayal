<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\Arguments\DurationArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TextArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Menus\Kingdom\ConfirmVoteProposalMenu;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KingdomKickSubCommand extends WSubCommand
{
    private const PLAYER_ARGUMENT = 'player';
    private const DURATION_ARGUMENT = 'duration';
    private const REASON_ARGUMENT = 'reason';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT));
        $this->registerArgument(1, new DurationArgument(self::DURATION_ARGUMENT, true));
        $this->registerArgument(2, new TextArgument(self::REASON_ARGUMENT, true));
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

        $target = $this->main->getServer()->getPlayerExact($args[self::PLAYER_ARGUMENT]);
        if ($target === null) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $args[self::PLAYER_ARGUMENT]]);
            return;
        }

        if (strtolower($target->getName()) === strtolower($sender->getName())) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_KICK_SELF);
            return;
        }

        $targetSession = Session::get($target);
        if ($targetSession->getKingdom() === null || $targetSession->getKingdom()->id !== $kingdom->id) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_KICK_NOT_MEMBER, [ExtraTags::PLAYER => $target->getDisplayName(), ExtraTags::KINGDOM => $kingdom->getDisplayName()]);
            return;
        }

        $duration = $args[self::DURATION_ARGUMENT];
        $reason = (string)($args[self::REASON_ARGUMENT] ?? MessagesUtils::defaultReason("Discipline"));

        ConfirmVoteProposalMenu::sendTo($sender, $kingdom->id, KingdomVoteType::Kick, $target->getName(), $duration, $reason);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_KICK);
    }
}