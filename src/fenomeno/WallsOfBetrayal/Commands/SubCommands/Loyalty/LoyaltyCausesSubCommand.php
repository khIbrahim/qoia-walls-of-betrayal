<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Loyalty;

use fenomeno\WallsOfBetrayal\Commands\Arguments\FilterArgument;
use fenomeno\WallsOfBetrayal\Commands\Arguments\SortArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Inventory\Loyalty\LoyaltyCausesInventory;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class LoyaltyCausesSubCommand extends WSubCommand
{

    public const FILTER_TYPE_ARGUMENT = 'filter_type';
    public const SORT_TYPE_ARGUMENT   = 'sort_type';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void {
        $this->addConstraint(new InGameRequiredConstraint($this));

        $this->registerArgument(0, new FilterArgument(self::FILTER_TYPE_ARGUMENT, true));
        $this->registerArgument(1, new SortArgument(self::SORT_TYPE_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $filter = strtolower($args[self::FILTER_TYPE_ARGUMENT] ?? LoyaltyCausesInventory::FILTER_ALL);
        $sort   = strtolower($args[self::SORT_TYPE_ARGUMENT] ?? LoyaltyCausesInventory::FILTER_SORT_DESC);

        if(! in_array($filter, LoyaltyCausesInventory::ALLOWED_FILTERS, true)) {
            $filter = LoyaltyCausesInventory::FILTER_ALL;
        }

        if(! in_array($sort, LoyaltyCausesInventory::ALLOWED_SORT_FILTERS, true)) {
            $sort = LoyaltyCausesInventory::FILTER_SORT_DESC;
        }

        (new LoyaltyCausesInventory($filter, $sort))->send($sender);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::LOYALTY_CAUSES);
    }
}