<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\Arguments\KitArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Handlers\KitClaimHandler;
use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class GiveKitCommand extends WCommand
{

    private const PLAYER_ARGUMENT = 'player';
    private const KIT_ARGUMENT = 'kit';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT));
        $this->registerArgument(1, new KitArgument(self::KIT_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = (string) $args[self::PLAYER_ARGUMENT];
        $player     = $sender->getServer()->getPlayerByPrefix($playerName);
        if(! $player instanceof Player) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        /** @var null|Kit $kit */
        $kit = $args[self::KIT_ARGUMENT];
        if(! $kit){
            MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_KIT, [ExtraTags::KIT => (string) $args[self::KIT_ARGUMENT]]);
            return;
        }

        Await::g2c(
            Await::promise(function ($resolve, $reject) use ($sender, $kit, $player) {
                try {
                    KitClaimHandler::claim($player, $kit, true);
                    $resolve();
                } catch (Throwable $e){
                    $reject($e);
                }
            }),
            function () use ($kit, $player, $sender) {
                MessagesUtils::sendTo($sender, MessagesIds::KIT_GIVEN, [
                    ExtraTags::PLAYER => $player->getName(),
                    ExtraTags::KIT    => $kit->getDisplayName()
                ]);
            }, function (Throwable $e) use ($player, $kit, $sender) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("Error giving kit {$kit->getDisplayName()} to {$player->getName()}: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::GIVE_KIT);
    }
}