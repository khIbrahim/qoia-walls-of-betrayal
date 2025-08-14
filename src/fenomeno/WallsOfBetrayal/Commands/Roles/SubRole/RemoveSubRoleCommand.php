<?php

namespace fenomeno\WallsOfBetrayal\Commands\Roles\SubRole;

use fenomeno\WallsOfBetrayal\Class\Roles\Role;
use fenomeno\WallsOfBetrayal\Commands\Arguments\RoleArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerDontHaveSubRoleException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RoleNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RolePlayerNotFoundException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class RemoveSubRoleCommand extends WCommand
{

    private const TARGET_ARGUMENT   = 'player';
    private const ROLE_ARGUMENT     = 'role';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::TARGET_ARGUMENT, false));
        $this->registerArgument(1, new RoleArgument(self::ROLE_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = strtolower($args[self::TARGET_ARGUMENT]);
        /** @var null|Role $role */
        $role       = $args[self::ROLE_ARGUMENT];

        if(! $role){
            MessagesUtils::sendTo($sender, MessagesIds::ROLE_NOT_FOUND, [ExtraTags::AVAILABLE_ROLES => implode(", ", $this->main->getRolesManager()->getRolesNames())]);
            return;
        }

        if(is_array($role)) $role = reset($role);

        Await::f2c(function () use ($sender, $role, $playerName) {
            try {
                yield from $this->main->getRolesManager()->removeSubRole($playerName, $role);

                MessagesUtils::sendTo($sender, MessagesIds::SUBROLE_REMOVED, [
                    ExtraTags::PLAYER   => $playerName,
                    ExtraTags::SUBROLE  => $role->getDisplayName(),
                ]);
            } catch (PlayerDontHaveSubRoleException) {
                MessagesUtils::sendTo($sender, MessagesIds::PLAYER_DONT_HAVE_SUBROLE, [
                    ExtraTags::PLAYER => $playerName,
                    ExtraTags::SUBROLE   => $role->getDisplayName()
                ]);
            } catch (RoleNotFoundException) {
                MessagesUtils::sendTo($sender, MessagesIds::SUBROLE_NOT_FOUND, [ExtraTags::AVAILABLE_ROLES => implode(", ", $this->main->getRolesManager()->getRolesNames())]);
            } catch (RolePlayerNotFoundException) {
                MessagesUtils::sendTo($sender, MessagesIds::PLAYER_ROLE_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            } catch (DatabaseException|Throwable $e) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::REMOVE_SUB_ROLE);
    }
}