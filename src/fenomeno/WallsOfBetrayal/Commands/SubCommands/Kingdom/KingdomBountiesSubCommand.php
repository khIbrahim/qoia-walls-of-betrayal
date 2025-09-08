<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomBounty;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\BooleanArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class KingdomBountiesSubCommand extends WSubCommand
{
    private const PAGE_ARGUMENT = 'page';
    private const FILTER_ARGUMENT = 'filter';
    private const PER_PAGE = 8; // Combien de bounties par page

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));

        $this->registerArgument(0, new IntegerArgument(self::PAGE_ARGUMENT, true));
        $this->registerArgument(1, new BooleanArgument(self::FILTER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $page = (int)($args[self::PAGE_ARGUMENT] ?? 1);
        $page = max($page, 1);
        $filter = $args[self::FILTER_ARGUMENT] ?? false;

        $bounties = $this->main->getBountyManager()->getAll();
        $total = count($bounties);
        $maxPages = max(1, (int)ceil($total / self::PER_PAGE));

        if ($page > $maxPages) {
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_NO_PAGE, [
                ExtraTags::POSITION => $page,
                ExtraTags::AMOUNT => $maxPages
            ]);
            return;
        }

        $offset = ($page - 1) * self::PER_PAGE;
        $paginated = array_slice($bounties, $offset, self::PER_PAGE, true);

        MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_PAGE_HEADER, [
            ExtraTags::POSITION => $page,
            ExtraTags::AMOUNT => $maxPages
        ]);

        /**
         * @var int $i
         * @var KingdomBounty $bounty
         */
        foreach ($paginated as $i => $bounty) {
            $tags = [
                ExtraTags::POSITION => $offset + $i + 1,
                ExtraTags::TARGET => $bounty->getTargetPlayer(),
                ExtraTags::AMOUNT => $bounty->getAmount(),
                ExtraTags::PLAYER => $bounty->getPlacedBy(),
                ExtraTags::CREATED_AT => gmdate("Y-m-d H:i:s", $bounty->getCreatedAt()),
                ExtraTags::TAKEN => $bounty->getTakenBy() ?? 'Unknown',
                ExtraTags::BOOL => $bounty->isStrict() ? 'true' : 'false'
            ];
            if (!$filter && (!$bounty->isActive() || $bounty->getTakenBy() !== null)) {
                continue;
            }

            if ($filter && (!$bounty->isActive() || $bounty->getTakenBy() !== null)) {
                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_INACTIVE_ENTRY, $tags);
            } elseif (!$filter && $bounty->isActive() && $bounty->getTakenBy() === null) {
                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_BOUNTY_ACTIVE_ENTRY, $tags);
            }
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_BOUNTIES);
    }
}