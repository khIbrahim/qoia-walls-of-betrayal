<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Class\FloatingText;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Constants\CooldownTypes;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TextArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\BeaconBeamHelper;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use pocketmine\world\sound\BellRingSound;

class KingdomAlarmSubCommand extends WSubCommand
{
    private const COOLDOWN_DURATION = 60;
    private const REASON_ARGUMENT = 'reason';
    private const FT_ID = 'ft-kingdom-alarm:%s';

    private const BEAM_HEIGHT = 64;
    private const BEAM_INTERVAL = 20;
    private const BEAM_HALO = true;
    private const BEAM_HALO_RADIUS = 0.45;
    private const BEAM_DURATION = self::COOLDOWN_DURATION;

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new TextArgument(self::REASON_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = Session::get($sender);
        if (!$session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [ExtraTags::PLAYER => $sender->getName()]);
            return;
        }

        $kingdom = $session->getKingdom();
        if ($kingdom === null) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_IN_KINGDOM);
            return;
        }

        if (!$this->canActivateAlarm($kingdom)) {
            $cooldown = $this->getAlarmCooldown($kingdom);
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_ALARM_COOLDOWN, [ExtraTags::TIME => $cooldown]);
            return;
        }

        $reason = $args[self::REASON_ARGUMENT] ?? "";
        $this->activateAlarm($kingdom, $sender, $reason);

        MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_ALARM_SUCCESS);

        $kingdom->broadcastMessage(MessagesIds::KINGDOMS_ALARM_BROADCAST, [
            ExtraTags::PLAYER => $sender->getName(),
            ExtraTags::POSITION => $sender->getPosition()->__toString(),
            ExtraTags::REASON => $reason
        ]);

        $kingdom->broadcastSound(new BellRingSound());

        $this->setAlarmCooldown($kingdom);
    }

    private function canActivateAlarm(Kingdom $kingdom): bool
    {
        return !$this->main->getCooldownManager()->isOnCooldown(CooldownTypes::KINGDOM_ALARM, $kingdom->getId());
    }

    private function getAlarmCooldown(Kingdom $kingdom): string
    {
        return $this->main->getCooldownManager()->getCooldownRemaining(CooldownTypes::KINGDOM_ALARM, $kingdom->getId(), true);
    }

    /** @throws */
    private function activateAlarm(Kingdom $kingdom, Player $player, string $reason): void
    {
        BeaconBeamHelper::spawnBeaconBeam(
            $this->main,
            $player->getPosition(),
            self::BEAM_HEIGHT,
            $kingdom->getColor(),
            self::BEAM_INTERVAL,
            self::BEAM_HALO,
            self::BEAM_HALO_RADIUS,
            self::COOLDOWN_DURATION,
            $kingdom->getOnlineMembers()
        );

        $floatingText = new FloatingText(
            id: sprintf(self::FT_ID, $kingdom->id),
            position: Position::fromObject($player->getPosition()->add(0, 1.5, 0), $player->getWorld()),
            text: MessagesUtils::getMessage(MessagesIds::KINGDOMS_ALARM_FLOATING_TEXT, [
                ExtraTags::PLAYER => $player->getName(),
                ExtraTags::REASON => $reason
            ])
        );

        foreach ($kingdom->getOnlineMembers() as $p) {
            $floatingText->sendTo($p);

            $this->main->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($p, $floatingText) {
                $floatingText->hideFor($p);
            }), 20 * self::BEAM_DURATION);
        }
    }

    private function setAlarmCooldown(Kingdom $kingdom): void
    {
        $this->main->getCooldownManager()->setCooldown(CooldownTypes::KINGDOM_ALARM, $kingdom->getId(), self::COOLDOWN_DURATION);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_ALARM);
    }
}