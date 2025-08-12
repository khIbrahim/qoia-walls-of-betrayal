<?php

namespace fenomeno\WallsOfBetrayal\Commands\Economy;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Constants\Limits;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordMissingDatException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InsufficientFundsException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InvalidEconomyAmount;
use fenomeno\WallsOfBetrayal\Language\ExtraTags;
use fenomeno\WallsOfBetrayal\Language\MessagesIds;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use Generator;
use pocketmine\command\CommandSender;
use Throwable;

class PayCommand extends WCommand
{
    private const ARGUMENT_TARGET = "target";
    private const ARGUMENT_AMOUNT = "amount";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));

        $this->registerArgument(0, new RawStringArgument(PayCommand::ARGUMENT_TARGET));

        $this->registerArgument(1, new IntegerArgument(PayCommand::ARGUMENT_AMOUNT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = $args[PayCommand::ARGUMENT_TARGET];
        $amount = $args[PayCommand::ARGUMENT_AMOUNT];

        $playerExact = $sender->getServer()->getPlayerExact($player);
        if ($playerExact !== null) {
            $player = $playerExact->getName();
        }

        if (strtolower($sender->getName()) === strtolower($player)) {
            MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_PAY_SELF);
            return;
        }

        if (!is_numeric($amount)) {
            MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_INVALID);
            return;
        }

        if ($amount <= 0) {
            MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_SMALL);
            return;
        }

        if ($amount > Limits::INT63_MAX) {
            MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_LARGE);
            return;
        }

        Await::f2c(
            function () use ($amount, $sender, $player): Generator {
                try {
                    yield from $this->main->getEconomyManager()->transfer($sender, $player, $amount);

                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_PAY, [
                        ExtraTags::PLAYER => $player,
                        ExtraTags::BALANCE => $this->main->getEconomyManager()->getCurrency()->formatter->format($amount),
                    ]);

                    $target = $sender->getServer()->getPlayerExact($player);
                    if ($target !== null) {
                        MessagesUtils::sendTo($target, MessagesIds::BALANCE_PAY_RECEIVE, [
                            ExtraTags::PLAYER => $sender->getName(),
                            ExtraTags::BALANCE => $this->main->getEconomyManager()->getCurrency()->formatter->format($amount),
                        ]);
                    }
                } catch (EconomyRecordNotFoundException) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ACCOUNT_NONEXISTENT);
                } catch (InsufficientFundsException) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_ACCOUNT_INSUFFICIENT);
                } catch (DatabaseException $e) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_DATABASE, [ExtraTags::ERROR => $e->getPrevious()->getMessage()]);
                    $this->getOwningPlugin()->getLogger()->logException($e->getPrevious());
                } catch (EconomyRecordMissingDatException) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ACCOUNT_MISSING_DATA);
                } catch (InvalidEconomyAmount) {
                    MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_AMOUNT_INVALID);
                } catch (Throwable $e) {$sender->sendMessage($e->getMessage()); $this->main->getLogger()->logException($e);}
            }
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::PAY);
    }
}
