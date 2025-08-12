<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Handlers\JoinKingdomHandler;
use fenomeno\WallsOfBetrayal\Inventory\ChooseKingdomInventory;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ChooseCommand extends WCommand
{

    private const KINGDOM_ARGUMENT = 'kingdom';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(self::KINGDOM_ARGUMENT, true));
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        if(! isset($args[self::KINGDOM_ARGUMENT])) {
            (new ChooseKingdomInventory())->send($sender);
            return;
        }

        $kingdomId = (string) $args[self::KINGDOM_ARGUMENT];
        $kingdom = $this->main->getKingdomManager()->getKingdomById($kingdomId);
        if(! $kingdom){
            MessagesUtils::sendTo($sender, 'unknownKingdom', ['{KINGDOM}' => $kingdomId]);
            return;
        }

        JoinKingdomHandler::join($sender, $kingdom);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::CHOOSE);
    }
}