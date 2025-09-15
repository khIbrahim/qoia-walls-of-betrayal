<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum StoreMemberRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case SUPERVISOR = 'supervisor';
    case CASHIER = 'cashier';
    case EMPLOYEE = 'employee';
    case TRAINEE = 'trainee';

    public function getDisplayName(): string
    {
        return match($this) {
            self::OWNER => 'Propriétaire',
            self::MANAGER => 'Gérant',
            self::SUPERVISOR => 'Superviseur',
            self::CASHIER => 'Caissier',
            self::EMPLOYEE => 'Employé',
            self::TRAINEE => 'Stagiaire'
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::OWNER => 100,
            self::MANAGER => 80,
            self::SUPERVISOR => 60,
            self::CASHIER => 40,
            self::EMPLOYEE => 20,
            self::TRAINEE => 10
        };
    }

    public function canManage(StoreMemberRole $targetRole): bool
    {
        return $this->getLevel() > $targetRole->getLevel();
    }

    public function getPermissions(): array
    {
        return match($this) {
            self::OWNER => [
                'store.manage.all',
                'store.members.add',
                'store.members.remove',
                'store.members.promote',
                'store.members.demote',
                'store.finances.view',
                'store.finances.manage',
                'store.inventory.manage',
                'store.settings.manage',
                'store.reports.view'
            ],
            self::MANAGER => [
                'store.members.add',
                'store.members.remove',
                'store.members.promote.limited',
                'store.finances.view',
                'store.inventory.manage',
                'store.reports.view'
            ],
            self::SUPERVISOR => [
                'store.inventory.view',
                'store.inventory.manage.limited',
                'store.members.view',
                'store.reports.view.limited'
            ],
            self::CASHIER => [
                'store.transactions.process',
                'store.inventory.view',
                'store.customers.serve'
            ],
            self::EMPLOYEE => [
                'store.transactions.process',
                'store.inventory.view.limited',
                'store.customers.serve'
            ],
            self::TRAINEE => [
                'store.customers.serve'
            ]
        };
    }

    public static function getAllRoles(): array
    {
        return [
            self::OWNER,
            self::MANAGER,
            self::SUPERVISOR,
            self::CASHIER,
            self::EMPLOYEE,
            self::TRAINEE
        ];
    }
}