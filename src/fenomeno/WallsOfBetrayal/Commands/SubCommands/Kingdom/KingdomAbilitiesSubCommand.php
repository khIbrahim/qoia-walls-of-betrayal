<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KingdomAbilitiesSubCommand extends WSubCommand
{
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = Session::get($sender);
        if (!$session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [ExtraTags::PLAYER => $sender->getName()]);
            return;
        }

        $kingdom = $session->getKingdom();
        if ($kingdom === null) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_IN_KINGDOM);
            return;
        }

        $this->displayKingdomAbilities($sender, $kingdom);
    }

    private function displayKingdomAbilities(Player $player, $kingdom): void
    {
        if (empty($kingdom->abilities)) {
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_ABILITIES_NO_ABILITIES);
            return;
        }

        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_ABILITIES_HEADER);

        foreach ($kingdom->abilities as $abilityId) {
            $ability = $this->main->getAbilityManager()->getAbilityById($abilityId);
            if ($ability === null) continue;

            $cooldown = $this->main->getAbilityManager()->getCooldownRemaining($player, $abilityId);
            $cooldownText = $cooldown > 0 ? $this->formatTime($cooldown) : 'Ready';

            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_ABILITIES_ABILITY, [
                ExtraTags::ABILITY => $ability->getName(),
            ]);

            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_ABILITIES_COOLDOWN, [
                ExtraTags::TIME => $cooldownText
            ]);
        }
    }

    private function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        } else {
            return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_ABILITIES);
    }
}