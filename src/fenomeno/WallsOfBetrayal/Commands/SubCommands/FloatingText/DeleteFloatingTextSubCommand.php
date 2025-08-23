<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\FloatingText;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\FloatingText\UnknownFloatingTextException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use Throwable;

class DeleteFloatingTextSubCommand extends WSubCommand
{

    private const ID_ARGUMENT = 'id';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(self::ID_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $id = (string) $args[self::ID_ARGUMENT];

        Await::f2c(function () use($sender, $id) {
            try {
                $id = yield from $this->main->getFloatingTextManager()->removeFloatingText($id);

                MessagesUtils::sendTo($sender, MessagesIds::FLOATING_TEXT_REMOVE_SUCCESS, [ExtraTags::FLOATING_TEXT => $id]);
            } catch (UnknownFloatingTextException) {
                MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_FLOATING_TEXT, [ExtraTags::FLOATING_TEXT => $id]);
            } catch (Throwable $e) {Utils::onFailure($e, $sender, "Failed to remove floating text with id $id: " . $e->getMessage());}
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::FLOATING_TEXT_DELETE);
    }
}