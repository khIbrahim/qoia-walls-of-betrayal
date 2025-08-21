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
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StaffModCommand extends WCommand
{

    private const STATE_ARGUMENT = "state";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new BooleanArgument(self::STATE_ARGUMENT, true));
    }
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = StaffSession::get($sender);
        $newState = isset($args[self::STATE_ARGUMENT]) ? (bool) $args[self::STATE_ARGUMENT] : ! $session->isInStaffMode();
        $session->setInStaffMode($newState);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STAFF_MOD);
    }
}