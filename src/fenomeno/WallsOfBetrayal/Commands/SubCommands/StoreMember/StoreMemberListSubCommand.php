<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberStatus;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StoreMemberListSubCommand extends WSubCommand
{
    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        
        $this->registerArgument(0, new RawStringArgument("role", true));
        $this->registerArgument(1, new RawStringArgument("status", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $roleFilter = $args["role"] ?? null;
        $statusFilter = $args["status"] ?? null;

        // Check if sender has permission to list members
        Await::g2c(
            $this->main->getStoreMemberManager()->hasPermission($sender, 'store.members.view'),
            function (bool $hasPermission) use ($sender, $roleFilter, $statusFilter) {
                if (!$hasPermission) {
                    $sender->sendMessage("§cVous n'avez pas la permission de voir la liste des membres.");
                    return;
                }

                $this->processList($sender, $roleFilter, $statusFilter);
            },
            function (\Throwable $error) use ($sender) {
                // If player is not a store member, they can't see the list
                $sender->sendMessage("§cVous n'avez pas la permission de voir la liste des membres.");
            }
        );
    }

    private function processList(Player $sender, ?string $roleFilter, ?string $statusFilter): void
    {
        $promise = null;

        if ($roleFilter) {
            try {
                $role = StoreMemberRole::from(strtolower($roleFilter));
                $promise = $this->main->getStoreMemberManager()->getMembersByRole($role);
            } catch (\ValueError) {
                $roles = array_map(fn($r) => $r->value, StoreMemberRole::getAllRoles());
                $sender->sendMessage("§cRôle invalide. Rôles disponibles: " . implode(", ", $roles));
                return;
            }
        } elseif ($statusFilter) {
            try {
                $status = StoreMemberStatus::from(strtolower($statusFilter));
                $promise = $this->main->getStoreMemberManager()->getMembersByStatus($status);
            } catch (\ValueError) {
                $statuses = ['active', 'inactive', 'suspended', 'on_break', 'terminated'];
                $sender->sendMessage("§cStatut invalide. Statuts disponibles: " . implode(", ", $statuses));
                return;
            }
        } else {
            $promise = $this->main->getStoreMemberManager()->getAllMembers();
        }

        Await::g2c(
            $promise,
            function (array $members) use ($sender, $roleFilter, $statusFilter) {
                if (empty($members)) {
                    $sender->sendMessage("§cAucun membre trouvé.");
                    return;
                }

                $title = "§6§l--- Membres du Store";
                if ($roleFilter) $title .= " (Rôle: $roleFilter)";
                if ($statusFilter) $title .= " (Statut: $statusFilter)";
                $title .= " ---";

                $sender->sendMessage($title);
                $sender->sendMessage("§7Total: §f" . count($members) . " membre(s)");
                $sender->sendMessage("");

                foreach ($members as $member) {
                    $displayName = $member->getDisplayName();
                    $workingDays = $member->getWorkingDays();
                    $totalSales = number_format($member->getTotalSales(), 2);
                    
                    $sender->sendMessage("§7• {$displayName}");
                    $sender->sendMessage("  §7Jours de service: §f{$workingDays} §7| Ventes: §f{$totalSales}€");
                    
                    if ($member->getLastLogin()) {
                        $lastLogin = $member->getLastLogin()->format('d/m/Y H:i');
                        $sender->sendMessage("  §7Dernière connexion: §f{$lastLogin}");
                    }
                    $sender->sendMessage("");
                }
            },
            function (\Throwable $error) use ($sender) {
                $sender->sendMessage("§cErreur lors de la récupération de la liste: " . $error->getMessage());
            }
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STORE_MEMBER_LIST);
    }
}