<?php

namespace fenomeno\WallsOfBetrayal\Services;

use DateTimeImmutable;
use fenomeno\WallsOfBetrayal\Class\StoreMember\StoreMember;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use JsonSerializable;

class StoreMemberReportingService
{
    public function __construct(private readonly Main $main) {}

    /**
     * Generate comprehensive store member performance report
     */
    public function generatePerformanceReport(string $period = 'daily'): Generator
    {
        $members = yield from $this->main->getStoreMemberManager()->getAllMembers();
        $activeSessions = yield from $this->main->getStoreMemberManager()->getActiveSessions();
        
        $report = [
            'generated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'period' => $period,
            'summary' => [
                'total_members' => count($members),
                'active_members' => count(array_filter($members, fn($m) => $m->isActive())),
                'members_working' => count($activeSessions),
                'total_sales_today' => 0,
                'total_transactions_today' => 0
            ],
            'members' => [],
            'top_performers' => [
                'by_sales' => yield from $this->main->getStoreMemberManager()->getTopPerformersBySales(5),
                'by_transactions' => yield from $this->main->getStoreMemberManager()->getTopPerformersByTransactions(5)
            ]
        ];

        // Calculate today's totals from active sessions
        foreach ($activeSessions as $session) {
            $report['summary']['total_sales_today'] += $session->getSalesThisSession();
            $report['summary']['total_transactions_today'] += $session->getTransactionsThisSession();
        }

        // Add detailed member information
        foreach ($members as $member) {
            $memberData = $member->jsonSerialize();
            
            // Add current session info if active
            $activeSession = null;
            foreach ($activeSessions as $session) {
                if ($session->getMemberUuid() === $member->getUuid()) {
                    $activeSession = $session;
                    break;
                }
            }
            
            $memberData['current_session'] = $activeSession ? $activeSession->jsonSerialize() : null;
            $memberData['is_currently_working'] = $activeSession !== null;
            
            $report['members'][] = $memberData;
        }

        return $report;
    }

    /**
     * Generate sales analytics
     */
    public function generateSalesAnalytics(): Generator
    {
        $topBySales = yield from $this->main->getStoreMemberManager()->getTopPerformersBySales(10);
        $topByTransactions = yield from $this->main->getStoreMemberManager()->getTopPerformersByTransactions(10);
        
        $analytics = [
            'generated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'top_performers' => [
                'by_sales' => array_map(fn($m) => [
                    'username' => $m->getUsername(),
                    'role' => $m->getRole()->getDisplayName(),
                    'total_sales' => $m->getTotalSales(),
                    'total_transactions' => $m->getTotalTransactions(),
                    'average_sale' => $m->getAverageSalesPerTransaction(),
                    'working_days' => $m->getWorkingDays()
                ], $topBySales),
                'by_transactions' => array_map(fn($m) => [
                    'username' => $m->getUsername(),
                    'role' => $m->getRole()->getDisplayName(),
                    'total_transactions' => $m->getTotalTransactions(),
                    'total_sales' => $m->getTotalSales(),
                    'average_sale' => $m->getAverageSalesPerTransaction(),
                    'working_days' => $m->getWorkingDays()
                ], $topByTransactions)
            ],
            'role_statistics' => []
        ];

        // Calculate statistics by role
        $allMembers = yield from $this->main->getStoreMemberManager()->getAllMembers();
        $roleStats = [];
        
        foreach ($allMembers as $member) {
            $roleValue = $member->getRole()->value;
            if (!isset($roleStats[$roleValue])) {
                $roleStats[$roleValue] = [
                    'role' => $member->getRole()->getDisplayName(),
                    'count' => 0,
                    'total_sales' => 0,
                    'total_transactions' => 0,
                    'total_working_hours' => 0,
                    'active_members' => 0
                ];
            }
            
            $roleStats[$roleValue]['count']++;
            $roleStats[$roleValue]['total_sales'] += $member->getTotalSales();
            $roleStats[$roleValue]['total_transactions'] += $member->getTotalTransactions();
            $roleStats[$roleValue]['total_working_hours'] += $member->getTotalWorkingHours();
            
            if ($member->isActive()) {
                $roleStats[$roleValue]['active_members']++;
            }
        }
        
        // Calculate averages
        foreach ($roleStats as &$stats) {
            if ($stats['count'] > 0) {
                $stats['avg_sales_per_member'] = $stats['total_sales'] / $stats['count'];
                $stats['avg_transactions_per_member'] = $stats['total_transactions'] / $stats['count'];
                $stats['avg_hours_per_member'] = $stats['total_working_hours'] / $stats['count'];
            }
        }
        
        $analytics['role_statistics'] = array_values($roleStats);
        
        return $analytics;
    }

    /**
     * Generate productivity report
     */
    public function generateProductivityReport(): Generator
    {
        $members = yield from $this->main->getStoreMemberManager()->getAllMembers();
        $activeSessions = yield from $this->main->getStoreMemberManager()->getActiveSessions();
        
        $productivity = [
            'generated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'current_productivity' => [
                'active_workers' => count($activeSessions),
                'total_sales_in_progress' => array_sum(array_map(fn($s) => $s->getSalesThisSession(), $activeSessions)),
                'total_transactions_in_progress' => array_sum(array_map(fn($s) => $s->getTransactionsThisSession(), $activeSessions)),
                'total_hours_worked_today' => array_sum(array_map(fn($s) => $s->getSessionDurationHours(), $activeSessions))
            ],
            'efficiency_metrics' => [],
            'recommendations' => []
        ];

        // Calculate efficiency metrics
        foreach ($members as $member) {
            if ($member->getTotalWorkingHours() > 0) {
                $efficiency = [
                    'username' => $member->getUsername(),
                    'role' => $member->getRole()->getDisplayName(),
                    'sales_per_hour' => $member->getTotalSales() / $member->getTotalWorkingHours(),
                    'transactions_per_hour' => $member->getTotalTransactions() / $member->getTotalWorkingHours(),
                    'average_sale_value' => $member->getAverageSalesPerTransaction(),
                    'total_working_hours' => $member->getTotalWorkingHours(),
                    'working_days' => $member->getWorkingDays(),
                    'status' => $member->getStatus()->getDisplayName()
                ];
                
                $productivity['efficiency_metrics'][] = $efficiency;
            }
        }

        // Sort by sales per hour
        usort($productivity['efficiency_metrics'], fn($a, $b) => $b['sales_per_hour'] <=> $a['sales_per_hour']);

        // Generate recommendations
        $productivity['recommendations'] = $this->generateRecommendations($members, $activeSessions);
        
        return $productivity;
    }

    /**
     * Generate recommendations based on current data
     */
    private function generateRecommendations(array $members, array $activeSessions): array
    {
        $recommendations = [];
        
        // Check for underperforming members
        $activeMembers = array_filter($members, fn($m) => $m->isActive());
        if (count($activeMembers) > 0) {
            $avgSales = array_sum(array_map(fn($m) => $m->getTotalSales(), $activeMembers)) / count($activeMembers);
            
            $underperformers = array_filter($activeMembers, fn($m) => 
                $m->getTotalSales() < ($avgSales * 0.5) && $m->getWorkingDays() > 7
            );
            
            if (count($underperformers) > 0) {
                $recommendations[] = [
                    'type' => 'performance',
                    'priority' => 'high',
                    'title' => 'Membres sous-performants détectés',
                    'description' => count($underperformers) . ' membre(s) ont des ventes significativement inférieures à la moyenne.',
                    'action' => 'Envisager une formation supplémentaire ou un accompagnement.'
                ];
            }
        }

        // Check staffing levels
        $currentlyWorking = count($activeSessions);
        $totalActive = count($activeMembers);
        
        if ($totalActive > 0) {
            $workingRatio = $currentlyWorking / $totalActive;
            
            if ($workingRatio < 0.3) {
                $recommendations[] = [
                    'type' => 'staffing',
                    'priority' => 'medium',
                    'title' => 'Niveau de personnel faible',
                    'description' => "Seulement {$currentlyWorking}/{$totalActive} membres actifs travaillent actuellement.",
                    'action' => 'Encourager plus de membres à se connecter et commencer leurs sessions.'
                ];
            }
        }

        // Check for training needs
        $trainees = array_filter($activeMembers, fn($m) => 
            $m->getRole() === \fenomeno\WallsOfBetrayal\Enum\StoreMemberRole::TRAINEE && 
            $m->getWorkingDays() > 14
        );
        
        if (count($trainees) > 0) {
            $recommendations[] = [
                'type' => 'promotion',
                'priority' => 'low',
                'title' => 'Stagiaires prêts pour promotion',
                'description' => count($trainees) . ' stagiaire(s) travaillent depuis plus de 14 jours.',
                'action' => 'Envisager une promotion vers le rôle d\'employé.'
            ];
        }

        return $recommendations;
    }

    /**
     * Export report to JSON format for frontend consumption
     */
    public function exportReportToJson(array $report): string
    {
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generate formatted text report for in-game display
     */
    public function formatReportForDisplay(array $report): array
    {
        $lines = [];
        $lines[] = "§6§l--- Rapport des Membres du Store ---";
        $lines[] = "§7Généré le: §f" . $report['generated_at'];
        $lines[] = "";
        
        $summary = $report['summary'];
        $lines[] = "§e§lRésumé:";
        $lines[] = "§7Total membres: §f{$summary['total_members']}";
        $lines[] = "§7Membres actifs: §f{$summary['active_members']}";
        $lines[] = "§7Actuellement au travail: §f{$summary['members_working']}";
        $lines[] = "§7Ventes aujourd'hui: §f" . number_format($summary['total_sales_today'], 2) . "€";
        $lines[] = "§7Transactions aujourd'hui: §f{$summary['total_transactions_today']}";
        $lines[] = "";
        
        $lines[] = "§e§lTop 3 Vendeurs:";
        $topSales = array_slice($report['top_performers']['by_sales'], 0, 3);
        foreach ($topSales as $i => $performer) {
            $pos = $i + 1;
            $sales = number_format($performer->getTotalSales(), 2);
            $lines[] = "§7{$pos}. §f{$performer->getUsername()} §7- §f{$sales}€";
        }
        
        return $lines;
    }
}