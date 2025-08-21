<?php

namespace fenomeno\WallsOfBetrayal\Manager\Punishment;

use fenomeno\WallsOfBetrayal\Class\Punishment\Report;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Events\Punishment\NewReportEvent;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyReported;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\ReportNotFoundException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Services\NotificationService;
use Throwable;

class ReportManager
{

    /** @var Report[] */
    private array $reports = [];

    public function __construct(private readonly Main $main)
    {
        $this->load();
    }

    public function load(): void
    {
        Await::g2c(
            $this->main->getDatabaseManager()->getReportRepository()->getAll(),
            function (array $reports) {
                $this->reports = $reports;

                $this->removeExpired();
            }, function(Throwable $e) {
                $this->main->getLogger()->error("Failed to load reports: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    /**
     * @throws PlayerAlreadyReported
     */
    public function reportPlayer(string $target, string $reporter, string $reason): \Generator
    {
        $target = strtolower($target);

        if ($this->reportExists($target, $reporter)){
            throw new PlayerAlreadyReported("The player has already been reported");
        }

        /** @var Report $report */
        $report = yield from $this->main->getDatabaseManager()->getReportRepository()->create(new Report($target, $reason, $reporter));

        $this->reports[$report->getId()] = $report;

        (new NewReportEvent($target, $reporter, $reason))->call();

        NotificationService::broadcastReport($report);

        return $report;
    }

    public function reportExists(string $target, string $reporter): bool
    {
        foreach ($this->reports as $report) {
            if (strtolower($report->getTarget()) === strtolower($target) && strtolower($report->getStaff()) === strtolower($reporter)) {
                return true;
            }
        }
        return false;
    }

    public function removeExpired(): void
    {
        foreach ($this->reports as $report){
            if ($report->isExpired()){
                Await::g2c(
                    $this->removeReport($report->getId()),
                    fn(int $id) => $this->main->getLogger()->info("Removed Expired Report: " . $id),
                    function(Throwable $e) use ($report) {
                        $this->main->getLogger()->error("Failed to remove expired report with id : " . $report->getId());
                        $this->main->getLogger()->logException($e);
                    }
                );
            }
        }
    }

    /** @return Report[] */
    public function getReports(): array
    {
        return $this->reports;
    }

    /**
     * @throws ReportNotFoundException
     */
    public function delete(int $id): \Generator
    {
        if(! isset($this->reports[$id])){
            throw new ReportNotFoundException("Report with id $id not found.");
        }

        yield from $this->main->getDatabaseManager()->getReportRepository()->del(new IdPayload($id));

        unset($this->reports[$id]);

        return $id;
    }

}