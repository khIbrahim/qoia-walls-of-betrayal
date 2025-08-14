<?php

namespace fenomeno\WallsOfBetrayal\Commands\Roles;

use fenomeno\WallsOfBetrayal\Class\Roles\RolePlayer;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class PlayerRoleInfoCommand extends WCommand
{

    private const TARGET_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::TARGET_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = isset($args[self::TARGET_ARGUMENT]) ? strtolower((string) $args[self::TARGET_ARGUMENT]) : strtolower($sender->getName());
        $itSelf = strtolower($sender->getName()) === $player;

        $info = $this->main->getRolesManager()->getPlayer($player);
        if ($info){
            $this->sendInfo($sender, $player, $info, $itSelf);
            return;
        }

        $this->main->getRolesManager()->loadPlayer(
            null,
            $player,
            function (?RolePlayer $rolePlayer) use ($itSelf, $player, $sender) {
                if (! $rolePlayer){
                    MessagesUtils::sendTo($sender, MessagesIds::PLAYER_ROLE_NOT_FOUND, [ExtraTags::PLAYER => $player]);
                    return;
                }

                $this->sendInfo($sender, $player, $rolePlayer, $itSelf);
            }, function (Throwable $e) use ($sender) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("Error loading role player (with /role command): " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    private function sendInfo(CommandSender $sender, string $playerName, RolePlayer $rolePlayer, bool $itSelf): void
    {
        if ($itSelf){
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_ROLE_INFO_SELF, [
                ExtraTags::ROLE        => $rolePlayer->getRole()?->getDisplayName() ?? 'null',
                ExtraTags::DURATION    => DurationParser::getReadableDuration($rolePlayer->getExpiresAt()),
                ExtraTags::SUBROLES    => implode(', ', $rolePlayer->getSubRoles()),
                ExtraTags::PERMISSIONS => implode(', ', $rolePlayer->getPermissions()),
            ]);
        } else {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_ROLE_INFO_OTHER, [
                ExtraTags::PLAYER      => $playerName,
                ExtraTags::ROLE        => $rolePlayer->getRole()?->getDisplayName() ?? 'null',
                ExtraTags::DURATION    => DurationParser::getReadableDuration($rolePlayer->getExpiresAt()),
                ExtraTags::SUBROLES    => implode(', ', $rolePlayer->getSubRoles()),
                ExtraTags::PERMISSIONS => implode(', ', $rolePlayer->getPermissions()),
            ]);
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::PLAYER_ROLE_INFO);
    }
}