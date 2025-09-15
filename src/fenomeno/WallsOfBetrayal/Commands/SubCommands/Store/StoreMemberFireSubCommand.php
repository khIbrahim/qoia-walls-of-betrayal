<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\BaseSubCommand;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberFireSubCommand extends BaseSubCommand {

    public function __construct(private readonly Main $main, string $name, string $description) {
        parent::__construct($main, $name, $description);
    }

    protected function prepare(): void {
        $this->registerArgument(0, new TargetPlayerArgument("player", false));
        $this->registerArgument(1, new RawStringArgument("store", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);

        $targetPlayer = $this->getArgument($args, "player");
        $storeId = $this->getArgument($args, "store");

        if (!$targetPlayer instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Player not found or not online.");
            return;
        }

        Await::g2c(
            $this->main->getStoreMemberManager()->canManageStore($sender, $storeId),
            function (bool $canManage) use ($sender, $targetPlayer, $storeId) {
                if (!$canManage) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to manage this store.");
                    return;
                }

                Await::g2c(
                    $this->main->getStoreMemberManager()->fireMember(
                        $targetPlayer->getUniqueId()->toString(),
                        $storeId
                    ),
                    function ($member) use ($sender, $targetPlayer, $storeId) {
                        if ($member) {
                            $sender->sendMessage(TextFormat::GREEN . "Successfully fired {$targetPlayer->getName()} from store {$storeId}");
                            $targetPlayer->sendMessage(TextFormat::RED . "You have been fired from store {$storeId}.");
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Failed to fire member. They may not be a member of this store.");
                        }
                    },
                    function (\Throwable $error) use ($sender) {
                        $sender->sendMessage(TextFormat::RED . "Error firing member: " . $error->getMessage());
                    }
                );
            },
            function (\Throwable $error) use ($sender) {
                $sender->sendMessage(TextFormat::RED . "Error checking permissions: " . $error->getMessage());
            }
        );
    }
}