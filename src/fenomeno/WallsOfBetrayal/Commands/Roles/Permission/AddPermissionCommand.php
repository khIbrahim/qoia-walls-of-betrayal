<?php

namespace fenomeno\WallsOfBetrayal\Commands\Roles\Permission;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerAlreadyHasPermissionException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RolePlayerNotFoundException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class AddPermissionCommand extends WCommand
{

    private const TARGET_ARGUMENT = 'player';
    private const PERM_ARGUMENT   = 'permission';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::TARGET_ARGUMENT, false));
        $this->registerArgument(1, new RawStringArgument(self::PERM_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = strtolower($args[self::TARGET_ARGUMENT]);
        $permission = strtolower($args[self::PERM_ARGUMENT]);

        Await::f2c(function () use ($sender, $permission, $playerName) {
            try {
                yield from $this->main->getRolesManager()->addPermission($playerName, $permission);

                MessagesUtils::sendTo($sender, MessagesIds::PERMISSION_PLAYER_SET, [
                    ExtraTags::PLAYER     => $playerName,
                    ExtraTags::PERMISSION => $permission
                ]);
            } catch (PlayerAlreadyHasPermissionException) {
                MessagesUtils::sendTo($sender, MessagesIds::ALREADY_HAS_PERMISSION, [
                    ExtraTags::PLAYER => $playerName,
                    ExtraTags::PERMISSION => $permission
                ]);
            } catch (RolePlayerNotFoundException) {
                MessagesUtils::sendTo($sender, MessagesIds::PLAYER_ROLE_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            } catch (Throwable $e) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::ADD_PERMISSION);
    }
}