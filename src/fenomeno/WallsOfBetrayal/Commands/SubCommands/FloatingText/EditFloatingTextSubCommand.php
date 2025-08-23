<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\FloatingText;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Menus\FloatingText\FloatingTextMenus;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class EditFloatingTextSubCommand extends WSubCommand
{

    private const ID_ARGUMENT = 'id';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument(self::ID_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $id = (string)$args[self::ID_ARGUMENT];

        if(! $this->main->getFloatingTextManager()->exists($id)){
            MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_FLOATING_TEXT, [ExtraTags::FLOATING_TEXT => $id]);
            return;
        }

        FloatingTextMenus::sendEditMenu($sender, $this->main->getFloatingTextManager()->getFloatingText($id));
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::FLOATING_TEXT_EDIT);
    }
}