<?php

namespace fenomeno\WallsOfBetrayal\Commands\Economy;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordMissingDatException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Language\ExtraTags;
use fenomeno\WallsOfBetrayal\Language\MessagesIds;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class BalanceCommand extends WCommand
{

    private const ARGUMENT_PLAYER = "player";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(BalanceCommand::ARGUMENT_PLAYER, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = $args[BalanceCommand::ARGUMENT_PLAYER] ?? null;

        if (! $sender instanceof Player && $player === null) {
            $sender->sendMessage($this->getUsage());
            return;
        }

        if ($player !== null) {
            $playerExact = $sender->getServer()->getPlayerExact($player);

            if ($playerExact !== null) {
                $player = $playerExact->getName();
            }
        }

        $player ??= $sender->getName();

        $isSelf = $player === $sender->getName();

        Await::f2c(function () use ($isSelf, $sender, $player) {
            try {
                /** @var EconomyEntry $cacheEntry */
                $cacheEntry = yield from $this->main->getEconomyManager()->get($player);

                MessagesUtils::sendTo($sender, $isSelf ? MessagesIds::BALANCE_INFO : MessagesIds::BALANCE_OTHER_INFO, [
                    ExtraTags::PLAYER => $player,
                    ExtraTags::BALANCE => $this->main->getEconomyManager()->getCurrency()->formatter->format($cacheEntry->amount),
                    ExtraTags::BALANCE_POSITION => number_format($cacheEntry->position)
                ]);
            } catch (EconomyRecordMissingDatException) {
                MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ACCOUNT_MISSING_DATA);
            } catch (EconomyRecordNotFoundException) {
                MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ACCOUNT_NONEXISTENT);
            } catch (DatabaseException $e){
                MessagesUtils::sendTo($sender, MessagesIds::BALANCE_ERR_DATABASE, [ExtraTags::ERROR => $e->getMessage()]);
                $this->getOwningPlugin()->getLogger()->logException($e);
            } catch (Throwable $e){
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::BALANCE);
    }
}