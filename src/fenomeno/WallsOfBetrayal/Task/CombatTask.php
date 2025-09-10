<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\scheduler\Task;

class CombatTask extends Task
{

    public function __construct(private readonly Main $main){}

    public function onRun(): void
    {
        foreach ($this->main->getServer()->getOnlinePlayers() as $player) {
            if (! $player->isOnline() || ! $player->isConnected()) {
                continue;
            }

            if (! $this->main->getCombatManager()->isTagged($player)) {
                continue;
            }

            $this->main->getCombatManager()->cleanupInactiveOpponents($player);

            if ($this->main->getCombatManager()->noticePlayer()) {
                $time = $this->main->getCombatManager()->getRemainingCombatTime($player);
                if ($time === null) {
                    continue;
                }

                MessagesUtils::sendTo($player, MessagesIds::NOTICE_COMBAT, [
                    ExtraTags::TIME     => $time,
                    ExtraTags::OPPONENT => $this->main->getCombatManager()->getMainOpponent($player) ?? 'N/A',
                ]);
            }
        }
    }

}