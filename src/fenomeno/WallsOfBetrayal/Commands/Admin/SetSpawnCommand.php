<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
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

class SetSpawnCommand extends WCommand
{

    private const KINGDOM_ID_ARGUMENT = 'kingdom';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument(self::KINGDOM_ID_ARGUMENT)); // todo kingdom argument
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $kingdomId = (string) $args[self::KINGDOM_ID_ARGUMENT];
        $kingdom   = $this->main->getKingdomManager()->getKingdomById($kingdomId);
        if(! $kingdom instanceof Kingdom){
            MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_KINGDOM, [ExtraTags::KINGDOM => $kingdomId]);
            return;
        }

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
        return CommandsConfig::getCommandById(CommandsIds::SET_SPAWN);
    }
}