<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

class StoreMemberAddSubCommand extends WSubCommand
{
    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        
        $this->registerArgument(0, new RawStringArgument("player"));
        $this->registerArgument(1, new RawStringArgument("role"));
        $this->registerArgument(2, new RawStringArgument("store", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $playerName = $args["player"] ?? null;
        $roleName = $args["role"] ?? null;
        $storeId = $args["store"] ?? null;

        if (!$playerName || !$roleName) {
            $sender->sendMessage("§cUsage: /storemember add <player> <role> [store]");
            return;
        }

        // Check if sender has permission to add members
        Await::g2c(
            $this->main->getStoreMemberManager()->hasPermission($sender, 'store.members.add'),
            function (bool $hasPermission) use ($sender, $playerName, $roleName, $storeId) {
                if (!$hasPermission) {
                    $sender->sendMessage("§cVous n'avez pas la permission d'ajouter des membres.");
                    return;
                }

                $this->processAddMember($sender, $playerName, $roleName, $storeId);
            },
            function (\Throwable $error) use ($sender) {
                $sender->sendMessage("§cErreur lors de la vérification des permissions: " . $error->getMessage());
            }
        );
    }

    private function processAddMember(Player $sender, string $playerName, string $roleName, ?string $storeId): void
    {
        // Validate role
        try {
            $role = StoreMemberRole::from(strtolower($roleName));
        } catch (\ValueError) {
            $roles = array_map(fn($r) => $r->value, StoreMemberRole::getAllRoles());
            $sender->sendMessage("§cRôle invalide. Rôles disponibles: " . implode(", ", $roles));
            return;
        }

        // Get target player
        $targetPlayer = Server::getInstance()->getPlayerByPrefix($playerName);
        if (!$targetPlayer) {
            $sender->sendMessage("§cJoueur non trouvé ou hors ligne.");
            return;
        }

        $targetUuid = $targetPlayer->getUniqueId()->toString();

        // Check if player is already a member
        Await::g2c(
            $this->main->getStoreMemberManager()->getMemberByUuid($targetUuid),
            function ($existingMember) use ($sender, $targetPlayer, $role, $storeId, $targetUuid) {
                if ($existingMember) {
                    $sender->sendMessage("§c{$targetPlayer->getName()} est déjà membre du store.");
                    return;
                }

                // Check if sender can assign this role
                Await::g2c(
                    $this->main->getStoreMemberManager()->getMemberByPlayer($sender),
                    function ($senderMember) use ($sender, $targetPlayer, $role, $storeId, $targetUuid) {
                        if ($senderMember && !$senderMember->getRole()->canManage($role)) {
                            $sender->sendMessage("§cVous ne pouvez pas assigner un rôle de niveau supérieur ou égal au vôtre.");
                            return;
                        }

                        // Create the member
                        Await::g2c(
                            $this->main->getStoreMemberManager()->createMember(
                                $targetUuid,
                                $targetPlayer->getName(),
                                $role,
                                $storeId,
                                "Ajouté par " . $sender->getName()
                            ),
                            function ($newMember) use ($sender, $targetPlayer, $role) {
                                $roleDisplay = $role->getDisplayName();
                                $sender->sendMessage("§a{$targetPlayer->getName()} a été ajouté en tant que {$roleDisplay}.");
                                $targetPlayer->sendMessage("§aVous avez été ajouté au store en tant que {$roleDisplay}.");
                                
                                // Log the action
                                $this->main->getLogger()->info("{$sender->getName()} added {$targetPlayer->getName()} as {$role->value}");
                            },
                            function (\Throwable $error) use ($sender, $targetPlayer) {
                                $sender->sendMessage("§cErreur lors de l'ajout du membre: " . $error->getMessage());
                                $this->main->getLogger()->error("Failed to add store member: " . $error->getMessage());
                            }
                        );
                    },
                    function (\Throwable $error) use ($sender) {
                        $sender->sendMessage("§cErreur lors de la vérification des permissions: " . $error->getMessage());
                    }
                );
            },
            function (\Throwable $error) use ($sender) {
                $sender->sendMessage("§cErreur lors de la vérification du membre: " . $error->getMessage());
            }
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STORE_MEMBER_ADD);
    }
}