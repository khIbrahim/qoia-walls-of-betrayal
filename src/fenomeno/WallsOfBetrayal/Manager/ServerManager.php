<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\KingdomWorldNotFoundException;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\Server\LobbyManager;
use pocketmine\world\World;

final class ServerManager
{

    public const KINGDOM_WORLD = 'kingdoms';

    private LobbyManager $lobbyManager;

    private World $kingdomWorld;

    /**
     * @throws KingdomWorldNotFoundException
     */
    public function __construct(private readonly Main $main)
    {
        $this->lobbyManager = new LobbyManager($this->main);

        $this->main->getServer()->getWorldManager()->loadWorld(self::KINGDOM_WORLD);
        $kingdomWorld = $this->main->getServer()->getWorldManager()->getWorldByName(self::KINGDOM_WORLD);
        if(! $kingdomWorld){
            throw new KingdomWorldNotFoundException("Kingdom world not found");
        }

        $this->kingdomWorld = $kingdomWorld;
    }
    public function getLobbyManager(): LobbyManager
    {
        return $this->lobbyManager;
    }

    public function getKingdomsWorld(): World
    {
        return $this->kingdomWorld;
    }

}