<?php

namespace fenomeno\WallsOfBetrayal\Commands\Roles;

use fenomeno\WallsOfBetrayal\Class\Roles\Role;
use fenomeno\WallsOfBetrayal\Commands\Arguments\DurationArgument;
use fenomeno\WallsOfBetrayal\Commands\Arguments\RoleArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\PlayerAlreadyHasRoleException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RoleNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Roles\RolePlayerNotFoundException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class SetRoleCommand extends WCommand
{

    private const TARGET_ARGUMENT   = 'player';
    private const ROLE_ARGUMENT     = 'role';
    private const DURATION_ARGUMENT = 'duration';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::TARGET_ARGUMENT, false));
        $this->registerArgument(1, new RoleArgument(self::ROLE_ARGUMENT, false));
        $this->registerArgument(2, new DurationArgument(self::DURATION_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = strtolower($args[self::TARGET_ARGUMENT]);
        /** @var null|Role $role */
        $role       = $args[self::ROLE_ARGUMENT];
        $duration   = $args[self::DURATION_ARGUMENT] ?? null;

        if(! $role){
            MessagesUtils::sendTo($sender, MessagesIds::ROLE_NOT_FOUND, [ExtraTags::AVAILABLE_ROLES => implode(", ", $this->main->getRolesManager()->getRolesNames())]);
            return;
        }

        if(is_array($role)) $role = reset($role);

        if($duration) {
            $duration = (int) $duration;
        }

        Await::f2c(function () use ($sender, $duration, $role, $playerName) {
            try {
                yield from $this->main->getRolesManager()->setPlayerRole($playerName, $role, $duration);

                MessagesUtils::sendTo($sender, MessagesIds::ROLE_PLAYER_SET, [
                    ExtraTags::PLAYER   => $playerName,
                    ExtraTags::ROLE     => $role->getDisplayName(),
                    ExtraTags::DURATION => DurationParser::getReadableDuration($duration),
                ]);

                $player = $sender->getServer()->getPlayerExact($playerName);
                if ($player){
                    MessagesUtils::sendTo($player, MessagesIds::ROLE_SET, [
                        ExtraTags::ROLE     => $role->getDisplayName(),
                        ExtraTags::DURATION => DurationParser::getReadableDuration($duration),
                    ]);
                }
            } catch (DatabaseException $e) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            } catch (PlayerAlreadyHasRoleException) {
                MessagesUtils::sendTo($sender, MessagesIds::ALREADY_HAS_ROLE, [
                    ExtraTags::PLAYER => $playerName,
                    ExtraTags::ROLE   => $role->getDisplayName()
                ]);
            } catch (RoleNotFoundException) {
                MessagesUtils::sendTo($sender, MessagesIds::ROLE_NOT_FOUND, [ExtraTags::AVAILABLE_ROLES => implode(", ", $this->main->getRolesManager()->getRolesNames())]);
            } catch (RolePlayerNotFoundException) {
                MessagesUtils::sendTo($sender, MessagesIds::PLAYER_ROLE_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            } catch (Throwable $e) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SET_ROLE);
    }
}