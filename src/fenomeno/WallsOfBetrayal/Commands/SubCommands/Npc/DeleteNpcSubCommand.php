<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc;

use fenomeno\WallsOfBetrayal\Commands\Arguments\NpcArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Npc\UnknownNpcException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use Throwable;

final class DeleteNpcSubCommand extends WSubCommand
{

    private const ID_ARGUMENT = 'id';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new NpcArgument(self::ID_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $id = (string) $args[self::ID_ARGUMENT];

        Await::f2c(function () use ($sender, $id) {
            try {
                yield from $this->main->getNpcManager()->remove($id);

                MessagesUtils::sendTo($sender, MessagesIds::NPC_REMOVED, [ExtraTags::NPC => $id]);
            } catch (UnknownNpcException){
                MessagesUtils::sendTo($sender, MessagesIds::NPC_NOT_FOUND, [ExtraTags::NPC => $id]);
            } catch (Throwable $e){ Utils::onFailure($e, $sender, "Failed to remove npc $id on command by {$sender->getName()}: " . $e->getMessage()); }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::NPC_REMOVE);
    }
}