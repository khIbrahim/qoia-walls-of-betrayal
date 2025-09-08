<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomBounty;
use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomDataFilterArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\KingdomBountyAlreadyExists;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\BooleanArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
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
use Throwable;

class KingdomBountySubCommand extends WSubCommand
{

    private const PLAYER_ARGUMENT = 'player';
    private const PRIME_ARGUMENT = 'prime';
    private const STRICT_ARGUMENT = 'strict';

    private const MIN_BOUNTY = 100;
    private const MAX_BOUNTY = 1000000;

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));

        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT));
        $this->registerArgument(1, new IntegerArgument(self::PRIME_ARGUMENT));
        $this->registerArgument(2, new BooleanArgument(self::STRICT_ARGUMENT, true));
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

        $targetPlayer = $this->main->getServer()->getPlayerExact($args[self::PLAYER_ARGUMENT]);
        if ($targetPlayer === null) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $args[0]]);
            return;
        }

        if ($targetPlayer === $sender) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_SELF);
            return;
        }

        $amount = (int)$args[self::PRIME_ARGUMENT];
        if ($amount <= 0) {
            MessagesUtils::sendTo($sender, MessagesIds::INVALID_NUMBER, [
                ExtraTags::NUMBER => $amount,
                ExtraTags::MIN => self::MIN_BOUNTY,
                ExtraTags::MAX => self::MAX_BOUNTY
            ]);
            return;
        }

        $targetSession = Session::get($targetPlayer);
        if ($targetSession->getKingdom() === null || $targetSession->getKingdom()->id === $kingdom->id) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_NOT_ENEMY);
            return;
        }

        $kingdomBalance = $kingdom->getBalance();
        if ($kingdomBalance < $amount) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_INSUFFICIENT_FUNDS, [
                ExtraTags::AMOUNT => $amount
            ]);
            return;
        }

        $strict = $args[self::STRICT_ARGUMENT] ?? false;
        Await::f2c(function () use ($strict, $sender, $kingdom, $amount, $session, $targetPlayer) {
            try {
                /** @var KingdomBounty $bounty */
                $bounty = yield from $this->main->getBountyManager()->create($targetPlayer->getName(), $amount, $kingdom->getId(), $sender->getName(), $strict);
                yield from $kingdom->contribute(-$amount, KingdomDataFilterArgument::BALANCE);

                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_SUCCESS, [
                    ExtraTags::AMOUNT => $bounty->getAmount(),
                    ExtraTags::PLAYER => $bounty->getTargetPlayer()
                ]);

                $kingdom->broadcastMessage(MessagesIds::KINGDOMS_BOUNTY_SUCCESS, [
                    ExtraTags::AMOUNT => $bounty->getAmount(),
                    ExtraTags::PLAYER => $bounty->getTargetPlayer()
                ]);
            } catch (KingdomBountyAlreadyExists) {
                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_ALREADY_EXISTS, [ExtraTags::PLAYER => $targetPlayer->getName()]);
            } catch (Throwable $e) {
                Utils::onFailure($e, $targetPlayer, "Failed to place kingdom $kingdom->id bounty on {$targetPlayer->getName()}, amount $amount by {$sender->getName()}: " . $e->getMessage());
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_BOUNTY);
    }
}