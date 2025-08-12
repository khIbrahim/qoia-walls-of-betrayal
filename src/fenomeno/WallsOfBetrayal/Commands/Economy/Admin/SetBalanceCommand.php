<?php

namespace fenomeno\WallsOfBetrayal\Commands\Economy\Admin;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Constants\Limits;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InvalidEconomyAmount;
use fenomeno\WallsOfBetrayal\Language\MessagesIds;
use fenomeno\WallsOfBetrayal\Language\ExtraTags;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\FloatArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class SetBalanceCommand extends WCommand
{
    private const ARGUMENT_PLAYER = "player";
    private const ARGUMENT_AMOUNT = "amount";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(SetBalanceCommand::ARGUMENT_PLAYER));
        $this->registerArgument(1, new FloatArgument(SetBalanceCommand::ARGUMENT_AMOUNT));
    }

    /**
     * @param Player $sender
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = $args[SetBalanceCommand::ARGUMENT_PLAYER];
        $amount = $args[SetBalanceCommand::ARGUMENT_AMOUNT];

        $playerExact = $sender->getServer()->getPlayerExact($player);
        if ($playerExact !== null) {
            $player = $playerExact->getName();
        }

        if (! is_numeric($amount)) {
            MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_INVALID);
            return;
        }

        if ($amount < 0) {
            MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_SMALL);
            return;
        }

        if ($amount > Limits::INT63_MAX) {
            MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_LARGE);
            return;
        }

        Await::f2c(
            function () use ($sender, $player, $amount): Generator {
                try {
                    yield from $this->main->getEconomyManager()->set($player, $amount);

                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_SET, [
                        ExtraTags::PLAYER => $player,
                        ExtraTags::BALANCE => $this->main->getEconomyManager()->getCurrency()->formatter->format($amount),
                    ]);
                } catch (EconomyRecordNotFoundException) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ACCOUNT_NONEXISTENT);
                } catch (DatabaseException $exception) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_DATABASE, [ExtraTags::ERROR => $exception->getMessage()]);
                    $this->getOwningPlugin()->getLogger()->logException($exception);
                } catch (InvalidEconomyAmount) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_INVALID);
                } catch (Throwable $e) {$sender->sendMessage($e->getMessage()); $this->main->getLogger()->logException($e);}
            }
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SET_BALANCE);
    }
}
