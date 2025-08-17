<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\Vault\VaultOpenPayload;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Inventory\VaultInventory;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class VaultCommand extends WCommand
{

    private const NUMBER_ARGUMENT = 'number';
    private const PLAYER_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new IntegerArgument(self::NUMBER_ARGUMENT, true));
        $this->registerArgument(1, new TargetPlayerArgument(self::PLAYER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $number = $args[self::NUMBER_ARGUMENT] ?? 1;

        $maxVaultNumber = WobConfig::getMaxVaultNumber();
        if ($number > $maxVaultNumber) {
            MessagesUtils::sendTo($sender, MessagesIds::VAULT_NUMBER_TOO_HIGH, [ExtraTags::MAX => $maxVaultNumber]);
            return;
        }

        if ($number < 1) {
            MessagesUtils::sendTo($sender, MessagesIds::VAULT_NUMBER_TOO_LOW);
            return;
        }

        $player = $args[self::PLAYER_ARGUMENT] ?? $sender->getName();
        $itSelf = strtolower($player) === strtolower($sender->getName());

        $permission = PermissionIds::getVaultPerm($number);
        if ($itSelf && ! $sender->hasPermission($permission)) {
            MessagesUtils::sendTo($sender, MessagesIds::VAULT_NO_PERMISSION);
            return;
        }

        if (! $itSelf && ! $sender->hasPermission(PermissionIds::VAULT_OTHER)) {
            MessagesUtils::sendTo($sender, MessagesIds::VAULT_NO_PERMISSION_OTHER);
            return;
        }

        MessagesUtils::sendTo($sender, MessagesIds::VAULT_OPENING, [ExtraTags::NUMBER => $number, ExtraTags::PLAYER => $player]);
        Await::f2c(function () use($number, $player, $sender, $itSelf){
            try {
                $uuid     = $itSelf ? $sender->getUniqueId()->toString() : ($sender->getServer()->getPlayerExact($player)?->getUniqueId()->toString() ?? null);
                $contents = yield from $this->main->getDatabaseManager()->getVaultRepository()->open(new VaultOpenPayload($uuid, strtolower($player), $number));

                MessagesUtils::sendTo($sender, MessagesIds::VAULT_OPENED, [ExtraTags::NUMBER => $number, ExtraTags::PLAYER => $player]);
                (new VaultInventory($contents, $number))->send($sender);
            } catch (Throwable $e) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("Failed to load vault for player $player: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::VAULT);
    }
}