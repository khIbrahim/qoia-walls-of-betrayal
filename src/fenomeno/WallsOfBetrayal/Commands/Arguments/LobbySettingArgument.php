<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use fenomeno\WallsOfBetrayal\Manager\Server\LobbyManager;
use pocketmine\command\CommandSender;

class LobbySettingArgument extends StringEnumArgument
{

    public static array $VALUES = [
        LobbyManager::PVP => LobbyManager::PVP,
        LobbyManager::DAMAGE => LobbyManager::DAMAGE,
        LobbyManager::INTERACT => LobbyManager::INTERACT,
        LobbyManager::VOID_TP => LobbyManager::VOID_TP,
        LobbyManager::DROP => LobbyManager::DROP,
        LobbyManager::BUILD => LobbyManager::BUILD,
        LobbyManager::BREAK => LobbyManager::BREAK,
        LobbyManager::PICKUP => LobbyManager::PICKUP,
        LobbyManager::HUNGER => LobbyManager::HUNGER,
        LobbyManager::CLEAR => LobbyManager::CLEAR,
        LobbyManager::GIVE => LobbyManager::GIVE,
    ];

    public function parse(string $argument, CommandSender $sender): string
    {
        return static::$VALUES[$argument] ?? $argument;
    }

    public function getTypeName(): string{return "lobby_setting";}
    public function getEnumName(): string{return "lobby_setting";}
}