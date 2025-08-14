<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;

abstract class BaseAbility implements AbilityInterface
{
    protected array $activePlayers = [];
    protected array $particleSchedule = [];

    public function getColor(): string
    {
        return "#FFFFFF";
    }

    public function getRarity(): AbilityRarity
    {
        return AbilityRarity::COMMUN;
    }

    public function getManaCost(): int
    {
        return 0;
    }

    public function sendActivationMessage(Player $player): void
    {
        MessagesUtils::sendTo($player, 'abilities.activated', [
            '{RARITY_COLOR}' => $this->getRarity()->getColor(),
            '{ABILITY}'      => $this->getName(),
            '{USAGE}'        => $this->getUsageTime(),
        ]);

        $this->playActivationSound($player);
    }

    public function sendCooldownMessage(Player $player, int $remaining): void
    {
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $timeStr = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";

        MessagesUtils::sendTo($player, 'abilities.cooldown', [
            '{NAME}' => $this->getName(),
            '{TIME}' => $timeStr
        ]);

        $this->playErrorSound($player);
    }

    protected function getProgressBar(int $current, int $max): string
    {
        $percentage = ($current / $max) * 100;
        $filled = floor($percentage / 5);
        $empty = 20 - $filled;

        $bar = "§a" . str_repeat("█", $filled) . "§7" . str_repeat("█", $empty);
        return "§f" . $this->getName() . " §8[" . $bar . "§8] §e" . round($percentage) . '%';
    }

    protected function playActivationSound(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
            soundName: 'random.levelup',
            x: $player->getPosition()->x,
            y: $player->getPosition()->y,
            z: $player->getPosition()->z,
            volume: 1.0,
            pitch: 1.0
        ));
    }

    protected function playErrorSound(Player $player): void
    {
        $player->broadcastSound(new NoteSound(NoteInstrument::BASS_DRUM(), mt_rand(1, 255)));
    }

    public function displayVisualEffects(Player $player): void
    {
        // Implémentation par défaut - à override dans les classes enfants
    }

    protected function sendProgressBar(int $current, Player $player): void
    {
        $player->sendActionBarMessage($this->getProgressBar($current, $this->getUsageTime()));
    }

    public function getCost(): int
    {
        return 0;
    }

    public function onEnable(Player $player): void
    {
        $this->activePlayers[$player->getName()] = $player;
    }

    public function onDisable(Player $player): void
    {
        if (isset($this->activePlayers[$player->getName()])) {
            unset($this->activePlayers[$player->getName()]);
        }
    }

    public function getActivePlayers(): array
    {
        return $this->activePlayers;
    }
}