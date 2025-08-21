<?php

namespace fenomeno\WallsOfBetrayal\Menus\Punishment;

use fenomeno\WallsOfBetrayal\Class\Punishment\Report;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\ReportNotFoundException;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuOption;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\player\Player;
use Throwable;

class ReportsMenu
{

    private const REPORTS_PER_PAGE = 5;

    public static function sendTo(Player $player, Main $main, int $page = 1): void
    {
        $reports = $main->getPunishmentManager()->getReportManager()->getReports();

        $offset = ($page - 1) * self::REPORTS_PER_PAGE;
        $paginated = array_values(array_slice($reports, $offset, self::REPORTS_PER_PAGE, true));

        $options = [];
        foreach ($paginated as $report) {
            $options[] = new MenuOption("§f{$report->getTarget()} §7| §c{$report->getReason()}");
        }

        if (empty($options)) {
            $options[] = new MenuOption(MessagesUtils::getMessage(MessagesIds::REPORTS_EMPTY));
        }

        $form = new MenuForm(
            title: "§8Reports",
            text: MessagesUtils::getMessage(MessagesIds::REPORTS_HEADER, [ExtraTags::COUNT => count($reports)]),
            options: [
                ...$options,
                new MenuOption("Previous Page"),
                new MenuOption("Next Page")
            ],
            onSubmit: function(Player $player, int $selectedOption) use ($page, $main, $paginated): void{
                if ($selectedOption >= count($paginated)){
                    $action = $selectedOption - count($paginated);
                    switch($action){
                        case 0:
                            ReportsMenu::sendTo($player, $main, max($page - 1, 1));
                            break;
                        case 1:
                            ReportsMenu::sendTo($player, $main, $page + 1);
                            break;
                    }
                    return;
                }

                $report = $paginated[$selectedOption] ?? null;

                if ($report === null) {
                    return;
                }

                ReportsMenu::showReportDetails($player, $main, $report, $page);
            },
        );
        $player->sendForm($form);
    }

    public static function showReportDetails(Player $player, Main $main, Report $report, int $page = 1): void
    {
        $text = MessagesUtils::getMessage(MessagesIds::REPORT_DETAILS, [
            ExtraTags::PLAYER   => $report->getTarget(),
            ExtraTags::REPORTER => $report->getStaff(),
            ExtraTags::REASON   => $report->getReason(),
            ExtraTags::DATE     => date("d/m/Y H:i:s", $report->getCreatedAt())
        ]);

        $form = new MenuForm(
            title: $report->getTarget(),
            text: $text,
            options: [
                new MenuOption("Delete"),
                new MenuOption("<- Go Back")
            ],
            onSubmit: function(Player $player, int $selectedOption) use ($main, $report, $page): void {
                if($selectedOption === 0){
                    Await::f2c(function () use ($player, $report, $main) {
                        try {
                            $id = yield from $main->getPunishmentManager()->getReportManager()->delete($report->getId());

                            MessagesUtils::sendTo($player, MessagesIds::REPORT_REMOVED, [
                                ExtraTags::ID => $id
                            ]);
                        } catch (ReportNotFoundException){
                            MessagesUtils::sendTo($player, MessagesIds::REPORT_NOT_FOUND, [ExtraTags::ID => $report->getId()]);
                        } catch(Throwable $e){
                            MessagesUtils::sendTo($player, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                            $main->getLogger()->error("An error occurred while deleting report #{$report->getId()}: " . $e->getMessage());
                            $main->getLogger()->logException($e);
                        }
                    });
                } else {
                    ReportsMenu::sendTo($player, $main, $page);
                }
            }
        );

        $player->sendForm($form);
    }
}