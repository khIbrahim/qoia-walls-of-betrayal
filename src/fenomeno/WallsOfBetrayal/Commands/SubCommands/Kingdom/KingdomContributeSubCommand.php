<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomDataFilterArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\sound\XpCollectSound;
use Throwable;

class KingdomContributeSubCommand extends WSubCommand
{

    private const FILTER_ARGUMENT = 'filter';
    private const AMOUNT_ARGUMENT = 'amount';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new KingdomDataFilterArgument(self::FILTER_ARGUMENT, false));
        $this->registerArgument(1, new IntegerArgument(self::AMOUNT_ARGUMENT, true));
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

        $type = strtolower($args[self::FILTER_ARGUMENT]);
        $amount = isset($args[self::AMOUNT_ARGUMENT]) ? (int)$args[self::AMOUNT_ARGUMENT] : null;

        switch ($type) {
            case KingdomDataFilterArgument::XP:
                $this->contributeXP($sender, $kingdom, $amount);
                break;
            case KingdomDataFilterArgument::BALANCE:
                $this->contributeMoney($sender, $kingdom, $amount);
                break;
            default:
                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_CONTRIBUTE_INVALID_TYPE);
                break;
        }
    }

    private function contributeXP(Player $player, Kingdom $kingdom, ?int $amount): void
    {
        $playerXP = $player->getXpManager()->getXpLevel();

        if ($amount === null) {
            $amount = $playerXP;
        }

        $amount = (int)$amount;

        if ($amount <= 0 || $amount > $playerXP) {
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_CONTRIBUTE_INSUFFICIENT, [
                ExtraTags::TYPE => KingdomDataFilterArgument::XP,
                ExtraTags::HAVE => $playerXP,
                ExtraTags::NEEDED => $amount
            ]);
            return;
        }

        Await::f2c(function () use ($kingdom, $amount, $player) {
            try {
                yield from $kingdom->contribute($amount, KingdomDataFilterArgument::XP);

                $player->getXpManager()->subtractXpLevels($amount);

                $kingdom->broadcastMessage(MessagesIds::KINGDOMS_CONTRIBUTE_SUCCESS, [
                    ExtraTags::PLAYER => $player->getName(),
                    ExtraTags::AMOUNT => $amount,
                    ExtraTags::TYPE => KingdomDataFilterArgument::XP,
                    ExtraTags::KINGDOM => $kingdom->displayName
                ]);

                $player->broadcastSound(new XpCollectSound());
            } catch (Throwable $e) {
                Utils::onFailure($e, $player, "Failed to add $amount xp to kingdom $kingdom->id by {$player->getName()}: {$e->getMessage()}");
            }
        });
    }

    private function contributeMoney(Player $player, Kingdom $kingdom, ?int $amount): void
    {
        Await::f2c(function () use ($player, $kingdom, $amount) {
            try {
                /** @var EconomyEntry $entry */
                $entry = yield from $this->main->getEconomyManager()->get($player);
                $playerMoney = $entry->amount;

                if ($amount === null) {
                    $amount = $playerMoney;
                }

                $amount = (int)$amount;

                if ($amount <= 0 || $amount > $playerMoney) {
                    MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_CONTRIBUTE_INSUFFICIENT, [
                        ExtraTags::TYPE => KingdomDataFilterArgument::BALANCE,
                        ExtraTags::HAVE => $playerMoney,
                        ExtraTags::NEEDED => $amount
                    ]);
                    return;
                }

                yield from $this->main->getEconomyManager()->subtract($player, $amount);
                yield from $kingdom->contribute($amount, KingdomDataFilterArgument::BALANCE);

                $kingdom->broadcastMessage(MessagesIds::KINGDOMS_CONTRIBUTE_SUCCESS, [
                    ExtraTags::PLAYER => $player->getName(),
                    ExtraTags::AMOUNT => $amount,
                    ExtraTags::TYPE => KingdomDataFilterArgument::BALANCE,
                    ExtraTags::KINGDOM => $kingdom->displayName
                ]);
            } catch (Throwable $e) {
                Utils::onFailure($e, $player, "Failed to contribute $amount balance to $kingdom->id by {$player->getName()}: " . $e->getMessage());
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_CONTRIBUTE);
    }
}