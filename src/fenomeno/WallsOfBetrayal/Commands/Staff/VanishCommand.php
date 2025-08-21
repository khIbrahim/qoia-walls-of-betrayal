<?php

namespace fenomeno\WallsOfBetrayal\Commands\Staff;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\BooleanArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Sessions\StaffSession;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class VanishCommand extends WCommand
{

    private const STATE_ARGUMENT = "state";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void{
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new BooleanArgument(self::STATE_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = StaffSession::get($sender);

        $newState = isset($args[self::STATE_ARGUMENT]) ? (bool)$args[self::STATE_ARGUMENT] : !$session->isVanished();
        $session->setVanish($newState);

        MessagesUtils::sendTo($sender, $newState ? MessagesIds::VANISH_ENABLED : MessagesIds::VANISH_DISABLED, []);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::VANISH);
    }
}