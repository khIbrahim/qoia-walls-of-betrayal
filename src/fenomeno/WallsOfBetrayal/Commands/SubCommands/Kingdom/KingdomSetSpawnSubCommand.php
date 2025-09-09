<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use Throwable;

class KingdomSetSpawnSubCommand extends WSubCommand
{

    private const KINGDOM_ARGUMENT = 'kingdom';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new KingdomArgument(self::KINGDOM_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        /** @var null|Kingdom $kingdom */
        $kingdom = $args[self::KINGDOM_ARGUMENT];
        if(! $kingdom){
            MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_KINGDOM, [ExtraTags::KINGDOM => (string) $args[self::KINGDOM_ARGUMENT]]);
            return;
        }

        $kingdomId = $kingdom->getId();
        Await::g2c(
            $kingdom->updateSpawn($sender->getLocation()),
            function (Location $newLocation) use ($kingdomId, $kingdom, $sender) {
                MessagesUtils::sendTo($sender, MessagesIds::SET_SPAWN_SUCCESS, [ExtraTags::KINGDOM => $kingdom->getDisplayName()]);

                $this->main->getLogger()->info($sender->getName() . " updated $kingdomId spawn location to " . $newLocation->__toString());
            },
            fn (Throwable $e) => Utils::onFailure($e, $sender, "Failed to update $kingdomId spawn location by {$sender->getName()}: " . $e->getMessage())
        );
    }


    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SET_SPAWN);
    }
}