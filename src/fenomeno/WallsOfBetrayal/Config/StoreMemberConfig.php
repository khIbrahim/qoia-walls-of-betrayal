<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\utils\Config;

class StoreMemberConfig
{
    private static Config $config;
    private static array $rolePermissions = [];
    private static array $commissionRates = [];

    public static function init(Main $main): void
    {
        self::$config = new Config($main->getDataFolder() . "store_members.yml", Config::YAML);
        
        // Load role permissions
        $permissions = self::$config->getNested("store_members.role_permissions", []);
        foreach (StoreMemberRole::getAllRoles() as $role) {
            self::$rolePermissions[$role->value] = $permissions[$role->value] ?? [];
        }
        
        // Load commission rates
        $rates = self::$config->getNested("store_members.commission_rates", []);
        foreach (StoreMemberRole::getAllRoles() as $role) {
            self::$commissionRates[$role->value] = (float)($rates[$role->value] ?? 0.0);
        }
        
        $main->getLogger()->info("§aStore Members configuration loaded successfully");
    }

    public static function getRolePermissions(StoreMemberRole $role): array
    {
        return self::$rolePermissions[$role->value] ?? [];
    }

    public static function getCommissionRate(StoreMemberRole $role): float
    {
        return self::$commissionRates[$role->value] ?? 0.0;
    }

    public static function getAutoClockOutTimeout(): int
    {
        return self::$config->getNested("store_members.sessions.auto_clock_out_timeout", 30);
    }

    public static function getMaxSessionDuration(): int
    {
        return self::$config->getNested("store_members.sessions.max_session_duration", 12);
    }

    public static function getMinBreakDuration(): int
    {
        return self::$config->getNested("store_members.sessions.min_break_duration", 15);
    }

    public static function isPerformanceTrackingEnabled(): bool
    {
        return self::$config->getNested("store_members.performance.enable_tracking", true);
    }

    public static function isSalesTrackingForCommissionEnabled(): bool
    {
        return self::$config->getNested("store_members.performance.track_sales_for_commission", true);
    }

    public static function getBonusThreshold(): float
    {
        return (float)self::$config->getNested("store_members.performance.bonus_threshold", 1000.0);
    }

    public static function getReviewPeriod(): int
    {
        return self::$config->getNested("store_members.performance.review_period", 30);
    }

    public static function shouldNotifyStatusChanges(): bool
    {
        return self::$config->getNested("store_members.notifications.notify_status_changes", true);
    }

    public static function shouldNotifyRoleChanges(): bool
    {
        return self::$config->getNested("store_members.notifications.notify_role_changes", true);
    }

    public static function shouldNotifyMilestones(): bool
    {
        return self::$config->getNested("store_members.notifications.notify_milestones", true);
    }

    public static function getStoreHours(): array
    {
        return self::$config->getNested("store_settings.opening_hours", []);
    }

    public static function getStoreLocations(): array
    {
        return self::$config->getNested("store_settings.locations", []);
    }

    public static function getAutoGenerateReports(): array
    {
        return self::$config->getNested("reports.auto_generate", []);
    }

    public static function getReportFormats(): array
    {
        return self::$config->getNested("reports.formats", ["json", "text"]);
    }

    public static function getReportRecipients(string $period): array
    {
        return self::$config->getNested("reports.recipients.{$period}", []);
    }

    public static function isShopTransactionTrackingEnabled(): bool
    {
        return self::$config->getNested("integration.track_shop_transactions", true);
    }

    public static function isEconomyIntegrationEnabled(): bool
    {
        return self::$config->getNested("integration.economy_integration", true);
    }

    public static function shouldSyncWithRolesSystem(): bool
    {
        return self::$config->getNested("integration.sync_with_roles_system", false);
    }

    public static function getStoreLocation(string $locationId): ?array
    {
        $locations = self::getStoreLocations();
        return $locations[$locationId] ?? null;
    }

    public static function isStoreOpen(string $day = null): bool
    {
        if ($day === null) {
            $day = strtolower(date('l')); // Current day
        }
        
        $hours = self::getStoreHours();
        if (!isset($hours[$day])) {
            return false; // No hours defined for this day
        }
        
        $storeHours = $hours[$day];
        if ($storeHours === 'closed') {
            return false;
        }
        
        [$open, $close] = explode('-', $storeHours);
        $currentTime = date('H:i');
        
        return $currentTime >= $open && $currentTime <= $close;
    }

    public static function getAllConfiguration(): array
    {
        return self::$config->getAll();
    }

    public static function saveConfiguration(): void
    {
        self::$config->save();
    }

    public static function reloadConfiguration(Main $main): void
    {
        self::init($main);
    }
}