<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\Server\LobbyManager;

class ServerManager
{

    public const KINGDOM_WORLD = 'kingdoms';

    private LobbyManager $lobbyManager;

    public function __construct(private readonly Main $main)
    {
        $this->lobbyManager = new LobbyManager($this->main);
    }
    public function getLobbyManager(): LobbyManager
    {
        return $this->lobbyManager;
    }

}