<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Class\Roles\RolePlayer;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use Throwable;

class RolesListener implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onCreation(PlayerCreationEvent $event): void
    {
        $networkSession = $event->getNetworkSession();
        $playerInfo     = $networkSession->getPlayerInfo();
        $uuid           = $playerInfo->getUuid()->toString();
        $name           = strtolower($playerInfo->getUsername());

        $this->main->getRolesManager()->loadPlayer(
            $uuid,
            $name,
            function (?RolePlayer $rolePlayer) use ($networkSession, $uuid, $name) {
                if ($rolePlayer !== null){
                    return;
                }

                $this->main->getRolesManager()->insertPlayer($uuid, $name, function () use ($name) {
                    $this->main->getLogger()->info("§aPlayer role ($name) successfully inserted.");
                }, function (Throwable $e) use ($networkSession, $name) {
                    $networkSession->disconnect("An error occurred while creating your account. Please try again later.");
                    $this->main->getLogger()->error("Failed to insert role record for $name: " . $e->getMessage());
                    $this->main->getLogger()->logException($e);
                });
            },
            function (Throwable $e) use ($name, $networkSession) {
                $networkSession->disconnect("An error occurred while creating your account. Please try again later.");
                $this->main->getLogger()->error("Failed to insert role record for $name: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

}