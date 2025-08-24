<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\Arguments\LobbySettingArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\BooleanArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class SetLobbySettingCommand extends WCommand
{

    private const SETTING_ARGUMENT = 'setting';
    private const STATE_ARGUMENT   = 'state';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new LobbySettingArgument(self::SETTING_ARGUMENT));
        $this->registerArgument(1, new BooleanArgument(self::STATE_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $setting = (string) $args[self::SETTING_ARGUMENT];
        $state   = (bool) $args[self::STATE_ARGUMENT];

        $this->main->getServerManager()->getLobbyManager()->setSetting($setting, $state);
        MessagesUtils::sendTo($sender, MessagesIds::SET_LOBBY_SETTING_SUCCESS, [
            ExtraTags::SETTING => $setting,
            ExtraTags::BOOL => $state ? 'true' : 'false'
        ]);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SET_LOBBY_SETTING);
    }
}