<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\Arguments\SellArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Handlers\ShopTransactionHandler;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Throwable;

class SellCommand extends WCommand
{

    private const SELL_ARGUMENT = "sell";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new SellArgument(self::SELL_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);
        $type = $args[self::SELL_ARGUMENT];

        if(! in_array($type, [SellArgument::ALL, SellArgument::HAND])){
            MessagesUtils::sendTo($sender, MessagesIds::INVALID_SELL_ARGUMENT);
            return;
        }

        if ($type === SellArgument::ALL) {
            Await::g2c(
                ShopTransactionHandler::sellAll($sender),
                function (int $total) use ($sender) {
                    if ($total > 0){
                        MessagesUtils::sendTo($sender, MessagesIds::SELL_ALL_SUCCESS, [
                            ExtraTags::BALANCE => $this->main->getEconomyManager()->getCurrency()->formatter->format($total)
                        ]);
                    } else {
                        MessagesUtils::sendTo($sender, MessagesIds::SELL_NOTHING_TO_SELL);
                    }
                },
                function (Throwable $error) use ($sender) {
                    MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $error->getMessage()]);
                    $this->main->getLogger()->error("Error while selling all items for player " . $sender->getName() . ": " . $error->getMessage());
                    $this->main->getLogger()->logException($error);
                }
            );
        } elseif ($type === SellArgument::HAND) {
            $item = $sender->getInventory()->getItemInHand();
            $shopItem = $this->main->getShopManager()->getShopItemByItem($item);
            if ($item->isNull() || $item->getCount() <= 0 || ! $shopItem) {
                MessagesUtils::sendTo($sender, MessagesIds::ITEM_NOT_SELLABLE, [ExtraTags::ITEM => $item->getName()]);
                return;
            }

            $qty = $item->getCount();
            Await::g2c(
                ShopTransactionHandler::sell($sender, $shopItem, $qty),
                function ($total) use ($qty, $shopItem, $sender) {
                    if ($total) {
                        $packSize = $shopItem->getPackSize();
                        $packs    = intdiv($qty, $packSize);
                        $soldQty  = $packs * $packSize;

                        MessagesUtils::sendTo($sender, MessagesIds::SHOP_SOLD, [
                            ExtraTags::ITEM        => TextFormat::clean($shopItem->getDisplayName()),
                            ExtraTags::QTY         => (string) $soldQty,
                            ExtraTags::TOTAL_PRICE => (string) $total
                        ]);

                        $leftover = $qty - $soldQty;
                        if ($leftover > 0) {
                            MessagesUtils::sendTo($sender, MessagesIds::SELL_LEFTOVER, [
                                ExtraTags::ITEM      => TextFormat::clean($shopItem->getDisplayName()),
                                ExtraTags::LEFTOVER  => (string)$leftover,
                                ExtraTags::PACK_SIZE => (string)$packSize
                            ]);
                        }
                    }
                },
                fn ($er) => MessagesUtils::sendTo($sender, 'shop.txnFailed', ['{ERR}' => (string)$er])
            );
        } else {
            MessagesUtils::sendTo($sender, MessagesIds::INVALID_SELL_ARGUMENT);
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SELL);
    }
}