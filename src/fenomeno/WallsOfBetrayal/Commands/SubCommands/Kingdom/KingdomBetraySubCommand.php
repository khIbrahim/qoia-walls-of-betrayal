<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KingdomBetraySubCommand extends WSubCommand
{
    protected function prepare(): void
    {
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_PLAYER);
            return;
        }

        if (count($args) < 1) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BETRAYAL_CONFIRM);
            return;
        }

        $session = Session::get($sender);
        if (! $session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [
                ExtraTags::PLAYER => $sender->getName()
            ]);
            return;
        }

        $currentKingdom = $session->getKingdom();
        if ($currentKingdom === null) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_IN_KINGDOM);
            return;
        }

        $targetKingdomId = $args[0];
        $targetKingdom = $this->main->getKingdomManager()->getKingdomById($targetKingdomId);
        
        if ($targetKingdom === null) {
            MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_KINGDOM, [
                ExtraTags::KINGDOM => $targetKingdomId
            ]);
            return;
        }

        if ($targetKingdom->id === $currentKingdom->id) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BETRAYAL_SELF);
            return;
        }

        // Vérifier si le joueur peut trahir (cooldown, loyauté, etc.)
        if (!$this->canBetray($sender, $currentKingdom->id)) {
            $cooldown = $this->getBetrayalCooldown($sender);
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BETRAYAL_COOLDOWN, [
                ExtraTags::TIME => $cooldown
            ]);
            return;
        }

        // Vérifier si c'est la phase de combat
        $currentPhase = $this->main->getPhaseManager()->getCurrentPhase();
        if ($currentPhase->value !== 'battle') {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BETRAYAL_NOT_BATTLE_PHASE);
            return;
        }

        // Demander confirmation
        if (count($args) < 2 || $args[1] !== 'confirm') {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BETRAYAL_CONFIRM, [
                ExtraTags::KINGDOM => $targetKingdom->displayName
            ]);
            return;
        }

        // Effectuer la trahison
        $this->performBetrayal($sender, $currentKingdom, $targetKingdom);
    }

    private function canBetray(Player $player, string $currentKingdomId): bool
    {
        // Vérifier le cooldown de trahison (24h minimum)
        if ($this->hasBetrayalCooldown($player)) {
            return false;
        }

        // Vérifier la loyauté du joueur
        $loyalty = $this->getPlayerLoyalty($player, $currentKingdomId);
        if ($loyalty > 80) { // Très loyal, ne peut pas trahir
            return false;
        }

        return true;
    }

    private function hasBetrayalCooldown(Player $player): bool
    {
        // TODO: Vérifier le cooldown de trahison dans la base de données
        return false;
    }

    private function getBetrayalCooldown(Player $player): string
    {
        // TODO: Calculer le temps restant du cooldown
        return "12h 30m";
    }

    private function getPlayerLoyalty(Player $player, string $kingdomId): int
    {
        // TODO: Calculer la loyauté basée sur:
        // - Temps passé dans le royaume
        // - Contributions
        // - Participations aux batailles
        // - Nombre de trahisons précédentes
        return 50; // Placeholder
    }

    private function performBetrayal(Player $player, $currentKingdom, $targetKingdom): void
    {
        // Calculer les conséquences de la trahison
        $loyalty = $this->getPlayerLoyalty($player, $currentKingdom->id);
        $xpPenalty = (int)(($loyalty / 100) * 1000); // Plus loyal = plus de pénalité
        $moneyPenalty = (int)(($loyalty / 100) * 5000);

        // Appliquer les pénalités
        $this->applyBetrayalPenalties($player, $xpPenalty, $moneyPenalty);

        // Transférer le joueur au nouveau royaume
        $this->transferPlayerToKingdom($player, $targetKingdom->id);

        // Mettre à jour le cooldown de trahison
        $this->setBetrayalCooldown($player);

        // Messages de succès
        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_BETRAYAL_SUCCESS, [
            ExtraTags::OLD_KINGDOM => $currentKingdom->displayName,
            ExtraTags::NEW_KINGDOM => $targetKingdom->displayName
        ]);

        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_BETRAYAL_CONSEQUENCES, [
            ExtraTags::XP => $xpPenalty,
            ExtraTags::MONEY => $moneyPenalty
        ]);

        // Annoncer la trahison à tous les joueurs
        $this->main->getServer()->broadcastMessage(MessagesIds::KINGDOMS_BETRAYAL_BROADCAST, [
            ExtraTags::PLAYER => $player->getName(),
            ExtraTags::OLD_KINGDOM => $currentKingdom->displayName,
            ExtraTags::NEW_KINGDOM => $targetKingdom->displayName
        ]);

        // Effets visuels et sonores
        $this->playBetrayalEffects($player);
    }

    private function applyBetrayalPenalties(Player $player, int $xpPenalty, int $moneyPenalty): void
    {
        // Retirer l'XP
        $currentXP = $player->getXpManager()->getXpLevel();
        $newXP = max(0, $currentXP - $xpPenalty);
        $player->getXpManager()->setXpLevel($newXP);

        // Retirer l'argent
        $this->removePlayerMoney($player, $moneyPenalty);
    }

    private function transferPlayerToKingdom(Player $player, string $newKingdomId): void
    {
        // TODO: Transférer le joueur au nouveau royaume dans la base de données
        // TODO: Mettre à jour la session
    }

    private function setBetrayalCooldown(Player $player): void
    {
        // TODO: Définir le cooldown de trahison (24h)
    }

    private function removePlayerMoney(Player $player, int $amount): void
    {
        // TODO: Retirer l'argent du joueur
    }

    private function playBetrayalEffects(Player $player): void
    {
        // TODO: Effets visuels et sonores dramatiques
        // Particules sombres, sons de trahison, etc.
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_BETRAYAL);
    }
}
