<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Class\Roles\Role;
use fenomeno\WallsOfBetrayal\Class\Roles\RolePlayer;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use fenomeno\WallsOfBetrayal\Utils\WobChatFormatter;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerJoinEvent;
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
            return;
        }

        Await::f2c(function () use ($networkSession, $uuid, $name) {
            try {
                /** @var RolePlayer $playerROle */
                $playerROle = yield from $this->main->getRolesManager()->loadPlayer($uuid, $name);

                if (!$playerROle instanceof RolePlayer) {
                    $networkSession->disconnect(MessagesUtils::getMessage(MessagesIds::UNSTABLE));
                    return;
                }

                $this->main->getLogger()->info("§aRoles - $name Successfully loaded");
            } catch (Throwable $e) {
                $networkSession->disconnect(MessagesUtils::getMessage(MessagesIds::UNSTABLE));
                $this->main->getLogger()->error("Error occurred while loading role player $name: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
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
        $player = $event->getPlayer();
        $rolePlayer = $this->main->getRolesManager()->getPlayer($player);
        if ($rolePlayer === null) {
            return;
        }

        $rolePlayer->applyTo($player);

        if ($rolePlayer->isExpired()) {
            Await::f2c(function () use ($player) {
                try {
                    /** @var Role $role */
                    $role = yield from $this->main->getRolesManager()->handleExpiredRole($player);

                    MessagesUtils::sendTo($player, MessagesIds::ROLES_EXPIRED_TO_DEFAULT, [
                        ExtraTags::ROLE => $role->getDisplayName()
                    ]);
                } catch (Throwable $e) {
                    Utils::onFailure($e, $player, 'Failed to handle expired role for ' . $player->getName() . ': ' . $e->getMessage());
                }
            });
        }
    }

}