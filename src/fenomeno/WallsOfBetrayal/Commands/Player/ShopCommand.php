<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Handlers\ShopTransactionHandler;
use fenomeno\WallsOfBetrayal\Inventory\Shop\ShopCategoryInventory;
use fenomeno\WallsOfBetrayal\Inventory\Shop\ShopItemsInventory;
use fenomeno\WallsOfBetrayal\Inventory\Shop\ShopMenus;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShopCommand extends WCommand
{

    private const SHOP_ARGUMENT  = 'shop';
    private const MODE_ARGUMENT  = 'mode';
    private const COUNT_ARGUMENT = 'count';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));

        $this->registerArgument(0, new RawStringArgument(self::SHOP_ARGUMENT, true));
        $this->registerArgument(1, new RawStringArgument(self::MODE_ARGUMENT, true));
        $this->registerArgument(2, new RawStringArgument(self::COUNT_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        if(isset($args[self::SHOP_ARGUMENT])){
            $id = (string) $args[self::SHOP_ARGUMENT];
            $category = $this->main->getShopManager()->getCategoryById($id);
            if($category){
                (new ShopItemsInventory($category))->send($sender);
                return;
            }

            $item = StringToItemParser::getInstance()->parse($id);
            if(! $item){
                MessagesUtils::sendTo($sender, 'shop.itemNotFound');
                return;
            }
            $shopItem = $this->main->getShopManager()->getShopItemByItem($item);
            if(! $shopItem){
                MessagesUtils::sendTo($sender, 'shop.itemNotFound');
                return;
            }

            if (isset($args[self::MODE_ARGUMENT])){
                $mode  = (string) $args[self::MODE_ARGUMENT];
                $modes = array_map('strtolower', [ShopMenus::BUY_MODE, ShopMenus::SELL_MODE]);
                if(! in_array($mode, $modes)){
                    MessagesUtils::sendTo($sender, 'shop.unknownMode', ['{MODE}' => $mode, '{MODES}' => implode(", ", $modes)]);
                    return;
                }

                if (isset($args[self::COUNT_ARGUMENT])){
                    $qty = Utils::parseQty((string) $args[self::COUNT_ARGUMENT]);
                    if ($qty === null){
                        MessagesUtils::sendTo($sender, 'shop.invalidQuantity');
                        return;
                    }

                    if ($mode === ShopMenus::BUY_MODE){
                        Await::g2c(
                            ShopTransactionHandler::buy(
                                player: $sender,
                                shopItem: $shopItem,
                                count: $qty,
                            ),
                            function ($total) use ($shopItem, $qty, $sender) {
                                if($total){
                                    MessagesUtils::sendTo($sender, 'shop.bought', [
                                        '{ITEM}'        => TextFormat::clean($shopItem->getDisplayName()),
                                        '{QTY}'         => (string) $qty,
                                        '{TOTAL_PRICE}' => (string) $total
                                    ]);
                                }
                            }, fn ($er) => MessagesUtils::sendTo($sender, 'shop.txnFailed', ['{ERR}' => (string) $er])
                        );
                    } else {
                        Await::g2c(
                            ShopTransactionHandler::sell(
                                player: $sender,
                                shopItem: $shopItem,
                                count: $qty
                            ), function ($total) use ($qty, $shopItem, $sender) {
                                if($total){
                                    MessagesUtils::sendTo($sender, 'shop.sold', [
                                        '{ITEM}' => TextFormat::clean($shopItem->getDisplayName()),
                                        '{QTY}' => (string)$qty,
                                        '{TOTAL_PRICE}' => (string)$total
                                    ]);
                                }
                            }, fn ($er) => MessagesUtils::sendTo($sender, 'shop.txnFailed', ['{ERR}' => (string) $er])
                        );
                    }

                    return;
                }

                ShopMenus::sendShopByMode($sender, $shopItem, $mode);
            } else {
                ShopMenus::sendChooseMode($sender, $shopItem);
            }

            return;
        }

        (new ShopCategoryInventory($sender))->send($sender);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById('shop');
    }
}