<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Handlers\AbilityUseHandler;
use fenomeno\WallsOfBetrayal\Inventory\AbilitiesInventory;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class AbilitiesCommand extends WCommand
{

    private const ABILITY_ARGUMENT = 'ability';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(self::ABILITY_ARGUMENT, true));

        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        if(! isset($args[self::ABILITY_ARGUMENT])) {
            (new AbilitiesInventory($sender))->send($sender);
            return;
        }

        $abilityId = (string) $args[self::ABILITY_ARGUMENT];
        $ability = $this->main->getAbilityManager()->getAbilityById($abilityId);
        if(! $ability){
            MessagesUtils::sendTo($sender, 'abilities.unknown', ['{ABILITY}' => $abilityId]);
            return;
        }

        AbilityUseHandler::use($sender, $abilityId);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById('abilities');
    }
}