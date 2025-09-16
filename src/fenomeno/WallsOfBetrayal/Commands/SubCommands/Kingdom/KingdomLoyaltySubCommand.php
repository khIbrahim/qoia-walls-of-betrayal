<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\SubCommands\SubCommand;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KingdomLoyaltySubCommand extends SubCommand
{
    public function __construct(Main $main)
    {
        parent::__construct($main, 'loyalty', 'Check loyalty information', '/kingdom loyalty [player]');
    }

    public function execute(CommandSender $sender, array $args): bool
    {
        if (!($sender instanceof Player)) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_PLAYER);
            return false;
        }

        $targetPlayer = $sender;
        $targetName = $sender->getName();

        // If a player argument is provided, check that player's loyalty
        if (isset($args[0])) {
            $targetPlayer = $this->main->getServer()->getPlayerByPrefix($args[0]);
            if (!$targetPlayer) {
                MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [
                    '{PLAYER}' => $args[0]
                ]);
                return false;
            }
            $targetName = $targetPlayer->getName();
        }

        $loyaltyManager = $this->main->getLoyaltyManager();
        $loyaltyPercentage = $loyaltyManager->getLoyaltyPercentage($targetPlayer);
        $loyaltyRank = $loyaltyManager->getLoyaltyRank($targetPlayer);

        if (!$loyaltyRank) {
            $sender->sendMessage("§cUnable to get loyalty information for {$targetName}.");
            return false;
        }

        if ($targetPlayer === $sender) {
            // Show own loyalty
            MessagesUtils::sendTo($sender, MessagesIds::LOYALTY_INSPECT_SELF, [
                '{RANK_COLOR}' => $loyaltyRank->getColor(),
                '{RANK}' => $loyaltyRank->getTag(),
                '{SCORE}' => $loyaltyPercentage,
                '{RANK_NAME}' => $loyaltyRank->getDisplayName()
            ]);
        } else {
            // Show other player's loyalty
            MessagesUtils::sendTo($sender, MessagesIds::LOYALTY_INSPECT_OTHER, [
                '{PLAYER}' => $targetName,
                '{RANK_COLOR}' => $loyaltyRank->getColor(),
                '{RANK}' => $loyaltyRank->getTag(),
                '{SCORE}' => $loyaltyPercentage,
                '{RANK_NAME}' => $loyaltyRank->getDisplayName()
            ]);
        }

        // Show additional information
        $sender->sendMessage("§7═══════════════════════════════");
        $sender->sendMessage("§7Loyalty Effects:");
        
        if ($loyaltyRank->hasAbilityAccess()) {
            $sender->sendMessage("§a+ Special abilities unlocked");
        }
        
        if ($loyaltyRank->canBetray()) {
            $sender->sendMessage("§4+ Can attack allies (Betrayal Mode)");
            $sender->sendMessage("§c+ Receives betrayal rewards");
        }
        
        if ($loyaltyRank->hasShopRestrictions()) {
            $sender->sendMessage("§c- Restricted shop access");
        }

        return true;
    }

    public function getPermission(): string
    {
        return 'wob.command.kingdom.loyalty';
    }
}