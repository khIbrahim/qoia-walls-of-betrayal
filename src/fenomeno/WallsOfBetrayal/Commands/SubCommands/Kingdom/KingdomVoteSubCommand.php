<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomVoteTypeArgument;
use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomVoteStatusArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\Menus\Kingdom\VoteListMenu;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class KingdomVoteSubCommand extends WSubCommand
{
    private const TYPE_ARGUMENT = 'type';
    private const STATUS_ARGUMENT = 'status';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new KingdomVoteTypeArgument(self::TYPE_ARGUMENT));
        $this->registerArgument(1, new KingdomVoteStatusArgument(self::STATUS_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = Session::get($sender);
        if (!$session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED);
            return;
        }

        $type = $args[self::TYPE_ARGUMENT];
        if (!$type) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOM_VOTE_INCORRECT_TYPE, [
                ExtraTags::AVAILABLE => implode(', ', array_map(fn($t) => $t->value, KingdomVoteType::cases()))
            ]);
            return;
        }

        $kingdom = Session::get($sender)->getKingdom();
        if (!$kingdom) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_IN_KINGDOM);
            return;
        }

        VoteListMenu::send($sender, $kingdom->id, $type, $args[self::STATUS_ARGUMENT] ?? null);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_VOTE);
    }
}