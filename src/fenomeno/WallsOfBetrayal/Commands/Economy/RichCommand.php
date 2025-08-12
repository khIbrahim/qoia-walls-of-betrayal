<?php

namespace fenomeno\WallsOfBetrayal\Commands\Economy;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Language\ExtraTags;
use fenomeno\WallsOfBetrayal\Language\MessagesIds;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class RichCommand extends WCommand
{

    private const ARGUMENT_PAGE = "page";
    private const DEFAULT_LIMIT = 10;

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument(RichCommand::ARGUMENT_PAGE, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $offset = $args[RichCommand::ARGUMENT_PAGE] ?? 0;
        $offset = max($offset, 1);
        $offset = ($offset - 1) * RichCommand::DEFAULT_LIMIT;

        Await::f2c(function () use ($offset, $sender, $args){
            $entries = yield from $this->main->getEconomyManager()->getTop(offset: $offset);
            if(count($entries) === 0){
                MessagesUtils::sendTo($sender, MessagesIds::ERROR_RICH_NO_RECORDS);
                return;
            }

            $entries = array_slice($entries, $offset, RichCommand::DEFAULT_LIMIT);

            MessagesUtils::sendTo($sender, MessagesIds::RICH_HEADER);

            /** @var EconomyEntry $entry */
            foreach ($entries as $entry) {
                MessagesUtils::sendTo($sender, MessagesIds::RICH_ENTRY, [
                    ExtraTags::PLAYER  => $entry->username,
                    ExtraTags::BALANCE => $this->main->getEconomyManager()->getCurrency()->formatter->format($entry->amount),
                    ExtraTags::BALANCE_POSITION => $entry->position,
                ]);
            }

            if($sender instanceof Player){
                try {
                    yield from $entry = $this->main->getEconomyManager()->get($sender);

                    MessagesUtils::sendTo($sender, MessagesIds::RICH_FOOTER, [
                        ExtraTags::BALANCE_POSITION => $entry->position ?? "N/A",
                    ]);
                } catch (Throwable $e){var_dump($e->getMessage());}
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::RICH);
    }
}