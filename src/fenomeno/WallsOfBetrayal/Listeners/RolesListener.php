<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Class\Roles\Role;
use fenomeno\WallsOfBetrayal\Class\Roles\RolePlayer;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\WobChatFormatter;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\ClosureTask;
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

        if ($this->main->getRolesManager()->getPlayer($name) !== null){
            return; // sûrement a été load par qlq d'autre sur commande /role ou un truc du genre jsp
        }

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

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();

        Await::f2c(function () use ($event, $player) {
            try {
                $message = yield from $this->main->getRolesManager()->formatChatMessage($player, $event->getMessage());
                $event->setFormatter(new WobChatFormatter($message));
            } catch (Throwable $e){
                $this->main->getLogger()->error("Error formatting chat message: " . $e->getMessage());
                $event->setFormatter(new WobChatFormatter($event->getMessage()));
            }
        });
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $this->main->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($event) {
            $player = $event->getPlayer();

            $rolePlayer = $this->main->getRolesManager()->getPlayer($player);
            if ($rolePlayer === null) {
                $this->main->getLogger()->warning("RolePlayer not found for player: " . $player->getName());
                return;
            }

            try {
                if ($rolePlayer->isExpired()) {
                    Await::g2c(
                        $this->main->getRolesManager()->handleExpiredRole($player),
                        function (Role $role) use ($player) {
                            MessagesUtils::sendTo($player, MessagesIds::ROLES_EXPIRED_TO_DEFAULT, [
                                ExtraTags::ROLE => $role->getDisplayName()
                            ]);
                        },
                        function (Throwable $e) use ($player) {
                            $this->main->getLogger()->error("Failed to handle expired role for {$player->getName()}: ".$e->getMessage());
                            $this->main->getLogger()->logException($e);
                        }
                    );
                    return;
                }

                $rolePlayer->applyTo($player);
            } catch (Throwable $e) {
                $this->main->getLogger()->error("Failed to apply role to player {$player->getName()}: ".$e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        }), 20 * 3);
    }

}