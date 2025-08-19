<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Nick\LogSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Nick\ResetSubCommand;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Services\NickService;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class NickCommand extends WCommand
{

    public const NICK_ARGUMENT = "nick";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerSubCommand(new ResetSubCommand($this->main));
        $this->registerSubCommand(new LogSubCommand($this->main));
        $this->registerArgument(0, new RawStringArgument(self::NICK_ARGUMENT, true));
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        if (NickService::getInstance()->hasNick($sender)) {
            MessagesUtils::sendTo($sender, MessagesIds::NICK_ALREADY_SET);
            return;
        }

        try {
            if (isset($args[self::NICK_ARGUMENT])) {
                $nick = $args[self::NICK_ARGUMENT];
                if ($nick === ""){
                    MessagesUtils::sendTo($sender, MessagesIds::NICK_EMPTY);
                    return;
                }

                if($this->plugin->getServer()->getPlayerExact($nick) !== null){
                    MessagesUtils::sendTo($sender, MessagesIds::NICK_ALREADY_USED);
                    return;
                }

                $this->handleCustomNick($sender, (string) $args[self::NICK_ARGUMENT]);
            } else {
                $this->handleGeneratedNick($sender);
            }
        } catch (Throwable $e) {$this->handleFailure($sender, $e);}
    }

    private function handleCustomNick(Player $sender, string $nick): void
    {
        $nick = trim($nick);

        Await::g2c(
            NickService::getInstance()->isValid($nick),
            function (bool $isValid) use ($sender, $nick): void {
                if ($isValid) {
                    NickService::getInstance()->setNick($sender, $nick);
                } else {
                    MessagesUtils::sendTo($sender, MessagesIds::NICK_INVALID);
                }
            },
            fn (Throwable $e) => $this->handleFailure($sender, $e)
        );
    }

    private function handleGeneratedNick(Player $sender): void
    {
        Await::g2c(
            NickService::getInstance()->generate(),
            function (?string $nick) use ($sender): void {
                if ($nick !== null && strlen($nick) >= NickService::MIN_LENGTH && strlen($nick) <= NickService::MAX_LENGTH) {
                    NickService::getInstance()->setNick($sender, $nick);
                } else {
                    MessagesUtils::sendTo($sender, MessagesIds::NICK_INVALID);
                }
            },
            fn (Throwable $e) => $this->handleFailure($sender, $e)
        );
    }

    private function handleFailure(Player $sender, Throwable $e): void
    {
        MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
        $this->main->getLogger()->error("Failed to execute /nick command for {$sender->getName()}: " . $e->getMessage());
        $this->main->getLogger()->logException($e);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::NICK);
    }
}