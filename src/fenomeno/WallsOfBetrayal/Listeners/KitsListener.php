<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\GhastSound;

class KitsListener implements Listener
{

    public function __construct(private readonly Main $main){}

    public function onKill(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $cause  = $entity->getLastDamageCause();

        if (! $cause instanceof EntityDamageByEntityEvent) {
            return;
        }
        $damager = $cause->getDamager();
        if (! $damager instanceof Player) {
            return;
        }

        $this->processKitRequirement($damager, $entity, KitRequirementType::KILL);
    }

    public function onBreak(BlockBreakEvent $event): void {
        $this->processKitRequirement($event->getPlayer(), $event->getBlock(), KitRequirementType::BREAK);
    }

    private function processKitRequirement(Player $player, mixed $target, KitRequirementType $type): void
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return;
        }

        $kingdom = $session->getKingdom();
        if ($kingdom === null){
            return;
        }

        foreach ($kingdom->getKits() as $kit) {
            if ($this->main->getPhaseManager()->getCurrentDay() < $kit->getUnlockDay()) {
                continue;
            }

            foreach ($kit->getRequirements() as $requirement){
                if ($requirement->isComplete()) {
                    continue;
                }

                if ($requirement->getType() !== $type) {
                    continue;
                }

                $handler = $this->main->getKitsManager()->getRequirementHandlerFactory()->make($type);
                if ($handler && $handler->handle($player, $requirement, $kit, $target)) {
                    $kingdom->broadcastMessage(MessagesUtils::getMessage('kits.requirementAchieved', [
                        '{PLAYER}' => $player->getName(),
                        '{KIT}' => $kit->getDisplayName()
                    ]));
                    $kingdom->broadcastSound(new BlazeShootSound());
                    if ($kit->isRequirementsAchieved()) {
                        $kingdom->broadcastMessage(MessagesUtils::getMessage('kits.achieved', [
                            '{PLAYER}' => $player->getName(),
                            '{KIT}' => $kit->getDisplayName()
                        ]));
                        $kingdom->broadcastSound(new GhastSound());
                    }
                }
            }
        }
    }

}