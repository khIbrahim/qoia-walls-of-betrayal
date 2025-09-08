<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;
use fenomeno\WallsOfBetrayal\Class\Punishment\Ban;
use fenomeno\WallsOfBetrayal\Config\StaffConfig;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\Payload\HistoryPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Punishment\HistoryAddPayload;
use fenomeno\WallsOfBetrayal\DTO\PunishmentHistoryEntry;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\Punishment\BanManager;
use fenomeno\WallsOfBetrayal\Manager\Punishment\MuteManager;
use fenomeno\WallsOfBetrayal\Manager\Punishment\ReportManager;
use fenomeno\WallsOfBetrayal\Task\PunishmentTask;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use Generator;
use Throwable;

final class PunishmentManager
{

    private BanManager $banManager;
    private MuteManager $muteManager;
    private ReportManager $reportManager;

    public function __construct(private readonly Main $main){
        $this->main->getDatabaseManager()->executeGeneric(Statements::INIT_HISTORY, [], function (){
            $this->main->getLogger()->info("§aTable `punishment_history` has been successfully init");
        });

        StaffConfig::init($this->main);

        $this->banManager    = new BanManager($this->main);
        $this->muteManager   = new MuteManager($this->main);
        $this->reportManager = new ReportManager($this->main);

        $this->main->getScheduler()->scheduleDelayedRepeatingTask(new PunishmentTask($this), 20 * 20, 20);
    }

    public function getBanManager(): BanManager
    {
        return $this->banManager;
    }

    public function getMuteManager(): MuteManager
    {
        return $this->muteManager;
    }

    public function getReportManager(): ReportManager
    {
        return $this->reportManager;
    }

    public function addToHistory(AbstractPunishment $punishment): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($punishment) {
            try {
                $payload = new HistoryAddPayload(
                    target: $punishment->getTarget(),
                    type: $punishment->getType(),
                    reason: $punishment->getReason(),
                    staff: $punishment->getStaff(),
                    expiration: $punishment->getExpiration() ?? 0
                );

                $this->main->getDatabaseManager()->executeInsert(Statements::HISTORY_ADD, $payload->jsonSerialize(), $resolve, $reject);
            } catch (Throwable $e){
                $reject($e);
            }
        });
    }



    public function getHistory(HistoryPayload $payload): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($payload) {
            Await::f2c(function () use ($payload, $resolve, $reject) {
                try {
                    $rows = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::HISTORY_GET, $payload->jsonSerialize());

                    $history = [];
                    foreach ($rows as $row) {
                        $history[] = new PunishmentHistoryEntry(
                            target: $payload->username,
                            type: $payload->type,
                            reason: $row['reason'] ?? "Unknown Reason",
                            staff: $row['staff'] ?? "Unknown Staff",
                            createdAt: (int) ($row['created_at'] ?? 0),
                            expiration: $row['expiration'] !== null ? (int)$row['expiration'] : null
                        );
                    }

                    $resolve($history);
                } catch (Throwable $e){
                    $reject($e);
                }
            });
        });
    }

    public function getBanScreenMessage(Ban $ban): string
    {
        $tags = [
            ExtraTags::PLAYER      => $ban->getTarget(),
            ExtraTags::STAFF       => $ban->getStaff() ?? "WobPunishment",
            ExtraTags::REASON      => $ban->getReason() ?? MessagesUtils::getMessage(MessagesIds::DEFAULT_REASON),
            ExtraTags::DURATION    => $ban->getDurationText(),
            ExtraTags::CREATED_AT  => date("d/m/Y H:i:s", $ban->getCreatedAt()),
            ExtraTags::APPEAL_LINK => "https://discord.gg/VVhDFWcVXT"
        ];

        if (! $ban->isPermanent()) {
            $unbanTs = $ban->getExpiration();
            $tags['{UNBAN_AT}']  = date('Y-m-d H:i (T)', $unbanTs);
            $tags['{REMAINING}'] = date("d/m/Y H:i:s", $ban->getExpiration());
            $messageId = MessagesIds::BAN_TEMP_SCREEN_MESSAGE;
        } else {
            $messageId = MessagesIds::BAN_PERM_SCREEN_MESSAGE;
        }

        return MessagesUtils::getMessage($messageId, $tags);
    }

}