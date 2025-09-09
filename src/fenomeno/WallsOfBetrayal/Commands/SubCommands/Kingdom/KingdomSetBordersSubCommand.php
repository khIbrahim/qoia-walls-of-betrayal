<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\Arguments\BorderArgument;
use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\InvalidBorderException;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\sound\XpLevelUpSound;
use Throwable;

class KingdomSetBordersSubCommand extends WSubCommand
{

    private const BORDER_ARGUMENT  = "border";
    private const KINGDOM_ARGUMENT = "kingdom";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new KingdomArgument(self::KINGDOM_ARGUMENT, false));
        $this->registerArgument(1, new BorderArgument(self::BORDER_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $kingdom = $args[self::KINGDOM_ARGUMENT];
        if(! $kingdom instanceof Kingdom){
            MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_KINGDOM, [ExtraTags::KINGDOM => (string) $args[self::KINGDOM_ARGUMENT]]);
            return;
        }

        $type = $args[self::BORDER_ARGUMENT];
        Await::f2c(function () use ($sender, $type, $kingdom) {
            try {
                yield from $kingdom->updateBorders($type, $sender->getLocation());

                MessagesUtils::sendTo($sender, MessagesIds::KINGDOM_BORDERS_UPDATED, [
                    ExtraTags::KINGDOM => $kingdom->getDisplayName(),
                    ExtraTags::TYPE => $type
                ], "You have updated {KINGDOM} kingdom borders to {TYPE} type");
                $sender->broadcastSound(new XpLevelUpSound(1));
            } catch (InvalidBorderException $e){
                MessagesUtils::sendTo($sender, $e->getMessage());
            } catch (InvalidArgumentException){
                var_dump('pourquoi ?');
                MessagesUtils::sendTo($sender, MessagesIds::INVALID_ARGUMENT, [ExtraTags::AVAILABLE => implode(", ", BorderArgument::$VALUES)]);
            } catch(Throwable $e){Utils::onFailure($e, $sender, "Failed to update kingdom $kingdom->id borders");}
        });

    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SET_BORDERS);
    }
}