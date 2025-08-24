<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Entities\Types\PortalEntity;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class PortalCommand extends WCommand
{

    private const KINGDOM_ARGUMENT = 'kingdom';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument(self::KINGDOM_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $kingdomId = (string) $args[self::KINGDOM_ARGUMENT];
        $kingdom   = $this->main->getKingdomManager()->getKingdomById($kingdomId);
        if(! $kingdom instanceof Kingdom){
            MessagesUtils::sendTo($sender, MessagesIds::UNKNOWN_KINGDOM, [ExtraTags::KINGDOM => $kingdomId]);
            return;
        }

        if (! $kingdom->portalId){
            MessagesUtils::sendTo($sender, MessagesIds::NO_PORTAL, [ExtraTags::KINGDOM => $kingdom->getDisplayName()]);
        }

        try {
            $entity = new PortalEntity($sender->getLocation(), $kingdom->portalId);
            $entity->spawnToAll();
            MessagesUtils::sendTo($sender, MessagesIds::PORTAL_SUCCESS, [ExtraTags::KINGDOM => $kingdom->getDisplayName()]);
        } catch (Throwable $e) {Utils::onFailure($e, $sender, "Failed to spawn portal for kingdom $kingdomId by {$sender->getName()}: " . $e->getMessage());}
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::PORTAL);
    }
}