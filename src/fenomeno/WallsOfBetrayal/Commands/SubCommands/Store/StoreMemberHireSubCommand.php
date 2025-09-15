<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\FloatArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\BaseSubCommand;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberHireSubCommand extends BaseSubCommand {

    public function __construct(private readonly Main $main, string $name, string $description) {
        parent::__construct($main, $name, $description);
    }

    protected function prepare(): void {
        $this->registerArgument(0, new TargetPlayerArgument("player", false));
        $this->registerArgument(1, new RawStringArgument("store", false));
        $this->registerArgument(2, new RawStringArgument("role", false));
        $this->registerArgument(3, new FloatArgument("salary", true));
        $this->registerArgument(4, new FloatArgument("commission", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);

        $targetPlayer = $this->getArgument($args, "player");
        $storeId = $this->getArgument($args, "store");
        $roleString = $this->getArgument($args, "role");
        $salary = $this->getArgument($args, "salary") ?? 0.0;
        $commission = $this->getArgument($args, "commission") ?? 0.0;

        if (!$targetPlayer instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Player not found or not online.");
            return;
        }

        // Validate role
        try {
            $role = StoreMemberRole::from(strtolower($roleString));
        } catch (\ValueError $e) {
            $roles = implode(", ", array_map(fn(StoreMemberRole $r) => $r->value, StoreMemberRole::getAllRoles()));
            $sender->sendMessage(TextFormat::RED . "Invalid role. Available roles: " . $roles);
            return;
        }

        Await::g2c(
            $this->main->getStoreMemberManager()->canManageStore($sender, $storeId),
            function (bool $canManage) use ($sender, $targetPlayer, $storeId, $role, $salary, $commission) {
                if (!$canManage) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to manage this store.");
                    return;
                }

                Await::g2c(
                    $this->main->getStoreMemberManager()->hireMember(
                        $targetPlayer->getUniqueId()->toString(),
                        $targetPlayer->getName(),
                        $storeId,
                        $role,
                        $salary,
                        $commission
                    ),
                    function ($member) use ($sender, $targetPlayer, $storeId, $role) {
                        if ($member) {
                            $sender->sendMessage(TextFormat::GREEN . "Successfully hired {$targetPlayer->getName()} as {$role->getDisplayName()} in store {$storeId}");
                            $targetPlayer->sendMessage(TextFormat::GREEN . "You have been hired as {$role->getDisplayName()} in store {$storeId}!");
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Failed to hire member. They may already be a member of this store.");
                        }
                    },
                    function (\Throwable $error) use ($sender) {
                        $sender->sendMessage(TextFormat::RED . "Error hiring member: " . $error->getMessage());
                    }
                );
            },
            function (\Throwable $error) use ($sender) {
                $sender->sendMessage(TextFormat::RED . "Error checking permissions: " . $error->getMessage());
            }
        );
    }
}