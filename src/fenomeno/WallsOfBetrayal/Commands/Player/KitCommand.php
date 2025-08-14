<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Handlers\KitClaimHandler;
use fenomeno\WallsOfBetrayal\Inventory\KitsInventory;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KitCommand extends WCommand
{

    private const KIT_ARGUMENT = 'kit';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(self::KIT_ARGUMENT, true));
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        if(! isset($args[self::KIT_ARGUMENT])) {
            (new KitsInventory($sender))->send($sender);
            return;
        }

        $kitId = (string) $args[self::KIT_ARGUMENT];
        $kit = $this->main->getKitsManager()->getKitById($kitId);
        if(! $kit){
            MessagesUtils::sendTo($sender, 'kits.unknown', ['{KIT}' => $kitId]);
            return;
        }

        KitClaimHandler::claim($sender, $kit);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KIT);
    }
}