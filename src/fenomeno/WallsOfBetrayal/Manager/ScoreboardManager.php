<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use WeakReference;

final class ScoreboardManager
{

    /** @var WeakReference<Player> */
    private WeakReference $sender;

    public string $displayName;

    public array $datas = [];

    public string|null $objectiveName = null;

    public function __construct(WeakReference $sender) {
        $this->sender = $sender;
    }

    /**
     * @return ?Player
     */
    public function getSender(): ?Player {
        return $this->sender->get();
    }

    public function addScoreboard(string $displayName, string $objective): void {
        if(! $this->getSender()?->isConnected()){
            return;
        }

        $packet = new SetDisplayObjectivePacket();
        $packet->displaySlot = "sidebar";
        $packet->objectiveName = $objective;
        $packet->displayName = $displayName;
        $packet->criteriaName = "dummy";
        $packet->sortOrder = 0;
        $this->getSender()?->getNetworkSession()->sendDataPacket($packet);
    }

    public function setLine(string $objective, int $line, string $message): void {
        if(! $this->getSender()?->isConnected()){
            return;
        }

        $entry = new ScorePacketEntry();
        $entry->scoreboardId = $line;
        $entry->objectiveName = $objective;
        $entry->score = $line;
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $entry->customName = $message;
        $packet = new SetScorePacket();
        $packet->type = $packet::TYPE_CHANGE;
        $packet->entries[] = $entry;
        $this->getSender()?->getNetworkSession()->sendDataPacket($packet);
    }

    public function removeLine(string $objective, int $line): void {
        if(! $this->getSender()?->isConnected()){
            return;
        }

        $entry = new ScorePacketEntry();
        $entry->scoreboardId = $line;
        $entry->objectiveName = $objective;
        $entry->score = $line;
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $packet = new SetScorePacket();
        $packet->type = $packet::TYPE_REMOVE;
        $packet->entries[] = $entry;
        $this->getSender()?->getNetworkSession()->sendDataPacket($packet);
    }

    public function removeScoreboard(string $objective): void {
        if(! $this->getSender()?->isConnected()){
            return;
        }

        $packet = new RemoveObjectivePacket();
        $packet->objectiveName = $objective;
        $this->getSender()?->getNetworkSession()->sendDataPacket($packet);
    }

}