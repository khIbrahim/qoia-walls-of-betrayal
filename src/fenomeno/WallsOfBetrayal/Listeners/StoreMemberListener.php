<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Handlers\StoreMemberTransactionHandler;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class StoreMemberListener implements Listener
{
    public function __construct(private readonly Main $main) {}

    /**
     * Handle player join - update last login for store members
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        
        Await::g2c(
            $this->main->getStoreMemberManager()->getMemberByPlayer($player),
            function ($member) use ($player) {
                if ($member) {
                    // Update last login
                    Await::g2c(
                        $this->main->getDatabaseManager()->getStoreMemberRepository()->updateLastLogin($member->getUuid()),
                        function () use ($player) {
                            // Welcome message for store members
                            $player->sendMessage("§aBonjour ! Vous êtes connecté en tant que membre du store.");
                            $player->sendMessage("§7Utilisez §e/storemember clockin§7 pour commencer votre journée de travail.");
                        },
                        function (\Throwable $error) {
                            $this->main->getLogger()->warning("Failed to update last login for store member: " . $error->getMessage());
                        }
                    );
                }
            },
            function (\Throwable $error) {
                // Player is not a store member, ignore
            }
        );
    }

    /**
     * Handle player quit - auto clock out if needed
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        
        Await::g2c(
            $this->main->getStoreMemberManager()->getMemberByPlayer($player),
            function ($member) use ($player) {
                if ($member) {
                    // Check if player has an active session
                    Await::g2c(
                        $this->main->getDatabaseManager()->getStoreMemberRepository()->getActiveSession($member->getUuid()),
                        function ($activeSession) use ($player, $member) {
                            if ($activeSession) {
                                // Auto clock out
                                Await::g2c(
                                    $this->main->getStoreMemberManager()->clockOut($player),
                                    function ($session) use ($player) {
                                        $this->main->getLogger()->info(
                                            "Auto clocked out store member {$player->getName()} on disconnect"
                                        );
                                    },
                                    function (\Throwable $error) {
                                        $this->main->getLogger()->warning(
                                            "Failed to auto clock out store member {$player->getName()}: " . $error->getMessage()
                                        );
                                    }
                                );
                            }
                        },
                        function (\Throwable $error) {
                            // No active session or error, ignore
                        }
                    );
                }
            },
            function (\Throwable $error) {
                // Player is not a store member, ignore
            }
        );
    }
}