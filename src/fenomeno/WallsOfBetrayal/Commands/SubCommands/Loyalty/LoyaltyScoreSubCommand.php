<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Loyalty;

use fenomeno\WallsOfBetrayal\Class\Player\PlayerLoyalty;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\RecordNotFoundException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;

class LoyaltyScoreSubCommand extends WSubCommand
{

    private const PLAYER_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $targetName = $args[self::PLAYER_ARGUMENT] ?? $sender->getName();
        $itSelf     = $targetName === $sender->getName();

        Await::f2c(function () use ($itSelf, $sender, $targetName) {
            try {
                /** @var PlayerLoyalty $loyalty */
                $loyalty     = yield from $this->main->getLoyaltyManager()->getPlayerLoyalty($targetName);
                $loyaltyRank = $loyalty->getLoyaltyRank();

                MessagesUtils::sendTo($sender, $itSelf ? MessagesIds::LOYALTY_INSPECT_SELF : MessagesIds::LOYALTY_INSPECT_OTHER, [
                    ExtraTags::PLAYER     => $targetName,
                    ExtraTags::RANK_COLOR => $loyaltyRank->getColor(),
                    ExtraTags::RANK       => $loyaltyRank->getTag(),
                    ExtraTags::SCORE      => $loyalty->score,
                    ExtraTags::RANK_NAME  => $loyaltyRank->getDisplayName()
                ]);

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
            } catch (RecordNotFoundException) {
                MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $targetName]);
            } catch(\Throwable $e){
                Utils::onFailure($e, $sender, "an error occurred while fetching loyalty score.");
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::LOYALTY_SCORE);
    }
}