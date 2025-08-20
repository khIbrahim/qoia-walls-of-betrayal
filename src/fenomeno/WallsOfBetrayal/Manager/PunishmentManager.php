<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;
use fenomeno\WallsOfBetrayal\Class\Punishment\Ban;
use fenomeno\WallsOfBetrayal\Class\Punishment\Mute;
use fenomeno\WallsOfBetrayal\Class\Punishment\Report;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\Payload\Punishment\HistoryAddPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\Events\Punishment\PlayerMutedEvent;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyBannedException;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyMutedException;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerNotBannedException;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerNotMutedException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Services\NotificationService;
use fenomeno\WallsOfBetrayal\Task\PunishmentTask;
use Generator;
use Throwable;

class PunishmentManager
{

    /** @var Mute[] */
    private array $activeMutes = [];

    /** @var Ban[] */
    private array $activeBans = [];

    /** @var Report[] */
    private array $activeReports = [];

    public function __construct(private readonly Main $main){
        $this->loadAll();
    }

    public function loadAll(): void
    {
        foreach (['ban', 'ban-ip', 'pardon', 'banlist', 'tell', 'kick'] as $cmd){
            $this->main->getServer()->getCommandMap()->unregister(
                $this->main->getServer()->getCommandMap()->getCommand($cmd)
            );
        }

        $this->main->getDatabaseManager()->executeGeneric(Statements::INIT_HISTORY, [], function (){
            $this->main->getLogger()->info("§aTable `punishment_history` has been successfully init");
        });

        $this->main->getScheduler()->scheduleRepeatingTask(new PunishmentTask($this), 20 * 60);

        $this->loadMutes();
        $this->loadReports();
        $this->loadBans();
    }

    private function loadMutes(): void
    {
        Await::g2c(
            $this->main->getDatabaseManager()->getMuteRepository()->getAll(),
            function (array $mutes) {
                $this->activeMutes = $mutes;
                $this->main->getLogger()->info("Loaded " . count($mutes) . " mutes (" . implode(', ', array_map(fn(Mute $mute) => $mute->getTarget(), $mutes)) . ")");
            }, function(Throwable $e) {
                $this->main->getLogger()->error("Failed to load mutes: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }


    private function loadBans(): void
    {
        Await::g2c(
            $this->main->getDatabaseManager()->getBanRepository()->getAll(),
            function (array $bans) {
                /** @var Ban $ban */
                foreach ($bans as $ban){
                    var_dump($ban->getTarget() . ' is expired ?', $ban->isExpired());
                    if (! $ban->isExpired()){
                        $this->activeBans[strtolower($ban->getTarget())] = $ban;
                    }
                }
                $this->main->getLogger()->info("Loaded " . count($this->activeBans) . " bans (" . implode(', ', array_map(fn(Ban $b) => $b->getTarget(), $this->activeBans)) . ")");
            }, function(Throwable $e) {
            $this->main->getLogger()->error("Failed to load bans: " . $e->getMessage());
            $this->main->getLogger()->logException($e);
        }
        );
    }

    private function loadReports(): void
    {
        Await::g2c(
            $this->main->getDatabaseManager()->getReportRepository()->getAll(),
            function (array $reports) {
                $this->activeReports = $reports;
                $this->main->getLogger()->info("Loaded " . count($reports) . " reports (" . implode(', ', array_map(fn(Report $r) => $r->getTarget(), $reports)) . ")");
            }, function(Throwable $e) {
                $this->main->getLogger()->error("Failed to load reports: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    /**
     * @throws PlayerAlreadyMutedException
     */
    public function mutePlayer(string $target, string $reason, string $staff, ?int $expiration = null): Generator
    {
        $target = strtolower($target);

        if (isset($this->activeMutes[$target]) && $this->activeMutes[$target]->isActive()) {
            throw new PlayerAlreadyMutedException("Player $target is already muted.");
        }

        /** @var Mute $mute */
        $mute = yield from $this->main->getDatabaseManager()->getMuteRepository()->create(new Mute($target, $reason, $staff, $expiration >= PHP_INT_MAX ? null : $expiration));

        $this->activeMutes[strtolower($target)] = $mute;

        (new PlayerMutedEvent($mute))->call();

        NotificationService::broadcastMute($mute);

        return $mute;
    }

    public function isMuted(string $target): bool
    {
        return isset($this->activeMutes[strtolower($target)]) && $this->activeMutes[strtolower($target)]->isActive();
    }

    /**
     * @throws PlayerNotMutedException
     */
    public function unmutePlayer(string $player): Generator
    {
        if(! isset($this->activeMutes[strtolower($player)])){
            throw new PlayerNotMutedException("Player $player is not muted.");
        }

        yield from $this->main->getDatabaseManager()->getMuteRepository()->delete(new UsernamePayload(strtolower($player)));

        unset($this->activeMutes[strtolower($player)]);

//        if (isset(MutedPlayerArgument::$VALUES[strtolower($player)])) {
//            unset(MutedPlayerArgument::$VALUES[strtolower($player)]);
//        }

        NotificationService::broadcastUnmute($player);

        return $player;
    }

        /** @return Mute[] */
    public function getActiveMutes(): array
    {
        return array_filter($this->activeMutes, fn(Mute $mute) => $mute->isActive());
    }

    public function getMute(string $target): ?Mute
    {
        return $this->activeMutes[$target] ?? null;
    }

    /**
     * @throws PlayerAlreadyBannedException
     */
    public function banPlayer(string $target, string $reason, string $staff, ?int $expiration = null, bool $silent = false): Generator
    {
        $target = strtolower($target);

        if($this->isBanned($target)){
            throw new PlayerAlreadyBannedException("Player $target is already banned.");
        }

        /** @var Ban $ban */
        $ban = yield from $this->main->getDatabaseManager()->getBanRepository()->create(new Ban($target, $reason, $staff, $expiration >= PHP_INT_MAX ? null : $expiration, $silent));

        $this->main->getServer()->getNameBans()->addBan($target, $reason, \DateTime::createFromFormat('U', $ban->getExpiration() ?? 0), $staff);
        $this->main->getServer()->getIPBans()->addBan($target, $reason, \DateTime::createFromFormat('U', $ban->getExpiration() ?? 0), $staff);

        if(! $ban->isSilent()){
            NotificationService::broadcastBan($ban);
        }

        return $ban;
    }

    public function isBanned(string $target): bool
    {
        foreach ($this->activeBans as $ban) {
            if (strtolower($ban->getTarget()) === strtolower($target)) {
                if ($ban->isActive()){
                    return true;
                } else {
                    Await::g2c($this->unbanPlayer($target));
                }
            }
        }

        return false;
    }

    /**
     * @throws PlayerNotBannedException
     */
    public function unbanPlayer(string $target): Generator
    {
        $target = strtolower($target);
        if(! $this->isBanned($target)){
            throw new PlayerNotBannedException("Player $target is not banned.");
        }

        $this->main->getServer()->getNameBans()->remove($target);
        $this->main->getServer()->getIPBans()->remove($target);

        yield from $this->main->getDatabaseManager()->getBanRepository()->delete(new UsernamePayload(strtolower($target)));

        unset($this->activeBans[strtolower($target)]);

        NotificationService::broadcastUnban($target);

        return $target;
    }

    public function getBan(string $name): ?Ban
    {
        foreach ($this->activeBans as $ban) {
            if (strtolower($ban->getTarget()) === strtolower($name)) {
                return $ban;
            }
        }
        return null;
    }

    public function getActiveBans(): array
    {
        return array_filter($this->activeBans, fn(Ban $ban) => $ban->isActive());
    }

//    public function createReport(string $target, string $reason, string $issuer): Promise
//    {
//        $promise = new PromiseResolver();
//
//        $report = new Report($target, $reason, $issuer, expiration: time() + 604800);
//        $this->reportRepository->create($report)
//            ->onCompletion(function (Report $report) use ($promise, $target) {
//                $this->activeReports[$report->getId()] = $report;
//                $this->addToHistory($report);
//
//                NotificationService::broadcastReport($report);
//
//                $promise->resolve($report);
//            }, function (Throwable $e) use ($promise) {
//                $this->main->getLogger()->error("§cErreur lors de la création du report: " . $e->getMessage());
//                $promise->reject();
//            });
//
//        return $promise->getPromise();
//    }
//
//    public function getActiveReports(): array
//    {
//        return array_filter($this->activeReports, fn(Report $report) => $report->isActive());
//    }
//
//    public function deleteReport(string $target): Promise
//    {
//        $resolver = new PromiseResolver();
//
//        if (! isset($this->activeReports[(int) $target])) {
//            $resolver->reject();
//            return $resolver->getPromise();
//        }
//
//        $this->reportRepository->delete($target)
//            ->onCompletion(
//                function() use ($target, $resolver) {
//                    unset($this->activeReports[(int) $target]);
//                    $resolver->resolve($target);
//                },
//                function(Throwable $e) use ($resolver) {
//                    $this->main->getLogger()->error("§cErreur lors de la suppression du report: " . $e->getMessage());
//                    $resolver->reject();
//                }
//            );
//
//        return $resolver->getPromise();
//    }
//
//    public function getArchivedReports(): array
//    {
//        return array_filter(
//            $this->activeReports,
//            fn(Report $report) => ! $report->isActive()
//        );
//    }
//
//    public function archiveReport(Report $report, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
//    {
//        $report->setActive(false);
//        $this->reportRepository->archiveReport($report, function() use ($onSuccess, $report) {
//            unset($this->activeReports[$report->getId()]);
//            $this->activeReports[$report->getId()] = $report;
//            if ($onSuccess !== null) {
//                $onSuccess($report);
//            }
//        },
//            $onFailure
//        );
//
//    }
//
//    public function reportExists(string $target, string $reporter): bool
//    {
//        foreach ($this->activeReports as $report) {
//            if (strtolower($report->getTarget()) === strtolower($target) && strtolower($report->getStaff()) === strtolower($reporter)) {
//                return true;
//            }
//        }
//        return false;
//    }

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

}