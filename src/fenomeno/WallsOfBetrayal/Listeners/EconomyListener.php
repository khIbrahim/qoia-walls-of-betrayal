<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use Throwable;

class EconomyListener implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onCreation(PlayerCreationEvent $event): void
    {
        $networkSession = $event->getNetworkSession();
        $playerInfo     = $networkSession->getPlayerInfo();
        $uuid           = $playerInfo->getUuid()->toString();
        $name           = strtolower($playerInfo->getUsername());

        Await::f2c(function () use ($networkSession, $uuid, $name) {
            try {
                /** @var EconomyEntry $entry */
                $entry = yield from $this->main->getEconomyManager()->get($name, $uuid);

                $position = $entry->position ?? 'N/A';
                $this->main->getLogger()->info("§aECONOMY - $name (#$position) has been successfully loaded");
            } catch (EconomyRecordNotFoundException){
                $this->main->getEconomyManager()->insert(
                    $name,
                    $uuid,
                    function () use ($name) {
                        $this->main->getLogger()->info("§aECONOMY - Record for $name successfully inserted.");
                    },
                    function (Throwable $e) use ($name, $networkSession) {
                        $networkSession->disconnect("An error occurred while creating your account. Please try again later.");
                        $this->main->getLogger()->error("Failed to insert economy record for $name: " . $e->getMessage());
                        $this->main->getLogger()->logException($e);
                    }
                );
            } catch (Throwable $e){
                $networkSession->disconnect("An error occurred while creating your account. Please try again later.");
                $this->main->getLogger()->error("Failed to load economy record for $name: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

}