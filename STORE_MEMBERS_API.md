# Store Members API - Documentation Complète

## Vue d'ensemble

Le système Store Members API est une implémentation complète de gestion des employés pour l'écosystème POS de restaurant dans le plugin Minecraft. Il offre une architecture modulaire, des performances optimisées et une facilité d'utilisation pour les développeurs frontend.

## Architecture

### Structure des Classes Principales

```
src/fenomeno/WallsOfBetrayal/
├── Class/StoreMember/
│   ├── StoreMember.php              # Entité principale du membre
│   └── StoreMemberSession.php       # Gestion des sessions de travail
├── Enum/
│   ├── StoreMemberRole.php          # Rôles (Owner, Manager, etc.)
│   └── StoreMemberStatus.php        # Statuts (Active, Inactive, etc.)
├── Database/
│   ├── Contrasts/Repository/
│   │   └── StoreMemberRepositoryInterface.php
│   └── Repository/
│       └── StoreMemberRepository.php
├── Manager/
│   └── StoreMemberManager.php       # Logique métier principale
├── Commands/
│   ├── StoreMemberCommand.php
│   └── SubCommands/StoreMember/     # Sous-commandes
├── Handlers/
│   └── StoreMemberTransactionHandler.php
├── Listeners/
│   └── StoreMemberListener.php
├── Services/
│   └── StoreMemberReportingService.php
└── Config/
    └── StoreMemberConfig.php
```

## Fonctionnalités Principales

### 1. Gestion des Membres

#### Rôles Disponibles
- **Owner** (Propriétaire) - Accès complet
- **Manager** (Gérant) - Gestion d'équipe et finances
- **Supervisor** (Superviseur) - Supervision et inventaire
- **Cashier** (Caissier) - Transactions et service client
- **Employee** (Employé) - Transactions de base
- **Trainee** (Stagiaire) - Service client supervisé

#### Statuts Possibles
- **Active** - Peut travailler normalement
- **Inactive** - Temporairement inactif
- **Suspended** - Suspendu
- **On Break** - En congé
- **Terminated** - Licencié

### 2. Commandes API

#### Commandes de Gestion
```
/storemember add <player> <role> [store]     # Ajouter un membre
/storemember remove <player>                 # Supprimer un membre
/storemember promote <player> <role>         # Promouvoir
/storemember demote <player> <role>          # Rétrograder
/storemember status <player> <status>        # Changer le statut
```

#### Commandes d'Information
```
/storemember list [role] [status]            # Lister les membres
/storemember info [player]                   # Infos détaillées
/storemember stats [player]                  # Statistiques
/storemember sessions [player]               # Historique des sessions
```

#### Commandes de Session
```
/storemember clockin                         # Commencer le travail
/storemember clockout                        # Terminer le travail
```

### 3. Suivi des Transactions

Le système suit automatiquement :
- Ventes effectuées par chaque membre
- Nombre de transactions
- Temps de travail
- Commission calculée selon le rôle

### 4. Système de Permissions

#### Permissions par Rôle
```yaml
owner:
  - store.manage.all
  - store.members.add/remove/promote/demote
  - store.finances.view/manage
  - store.inventory.manage
  - store.reports.view/generate

manager:
  - store.members.add/remove
  - store.members.promote.limited
  - store.finances.view
  - store.reports.view

# ... autres rôles
```

## Configuration

### store_members.yml

```yaml
store_members:
  role_permissions:
    # Permissions par rôle
  commission_rates:
    owner: 50.0
    manager: 20.0
    # ...
  sessions:
    auto_clock_out_timeout: 30
    max_session_duration: 12
  performance:
    enable_tracking: true
    bonus_threshold: 1000.0
```

## Base de Données

### Table: store_members
```sql
CREATE TABLE store_members (
    uuid VARCHAR(36) PRIMARY KEY,
    username VARCHAR(32) NOT NULL,
    role ENUM('owner', 'manager', 'supervisor', 'cashier', 'employee', 'trainee'),
    status ENUM('active', 'inactive', 'suspended', 'on_break', 'terminated'),
    hired_at TIMESTAMP NOT NULL,
    last_login TIMESTAMP NULL,
    total_working_hours INT DEFAULT 0,
    total_transactions INT DEFAULT 0,
    total_sales DECIMAL(10,2) DEFAULT 0.00,
    permissions JSON,
    store_id VARCHAR(64) NULL,
    last_promotion_at TIMESTAMP NULL,
    notes TEXT NULL
);
```

### Table: store_member_sessions
```sql
CREATE TABLE store_member_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_uuid VARCHAR(36) NOT NULL,
    clock_in_time TIMESTAMP NOT NULL,
    clock_out_time TIMESTAMP NULL,
    sales_this_session DECIMAL(10,2) DEFAULT 0.00,
    transactions_this_session INT DEFAULT 0,
    activities_log JSON
);
```

## Utilisation pour les Développeurs Frontend

### Format des Réponses JSON

#### Membre du Store
```json
{
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "username": "player_name",
    "role": "cashier",
    "status": "active",
    "hired_at": "2024-01-15 10:00:00",
    "last_login": "2024-01-20 14:30:00",
    "total_working_hours": 120,
    "total_transactions": 450,
    "total_sales": 15000.50,
    "working_days": 30,
    "average_sales_per_transaction": 33.33
}
```

#### Session de Travail
```json
{
    "member_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "clock_in_time": "2024-01-20 08:00:00",
    "clock_out_time": null,
    "sales_this_session": 250.75,
    "transactions_this_session": 8,
    "session_duration_hours": 6.5,
    "is_active": true
}
```

### Intégration avec l'API

#### Utilisation du Manager
```php
// Récupérer un membre
$member = yield from $main->getStoreMemberManager()->getMemberByUuid($uuid);

// Créer un membre
$member = yield from $main->getStoreMemberManager()->createMember(
    $uuid, $username, StoreMemberRole::CASHIER
);

// Commencer une session
yield from $main->getStoreMemberManager()->clockIn($player);

// Ajouter une vente
yield from $main->getStoreMemberManager()->addSale($player, 50.0);
```

#### Génération de Rapports
```php
$reportingService = new StoreMemberReportingService($main);

// Rapport de performance
$report = yield from $reportingService->generatePerformanceReport();

// Analytiques de ventes
$analytics = yield from $reportingService->generateSalesAnalytics();

// Export JSON pour frontend
$json = $reportingService->exportReportToJson($report);
```

## Optimisations et Performance

### Cache
- Mise en cache des membres fréquemment consultés
- Cache des sessions actives
- Optimisation des requêtes SQL

### Sécurité
- Validation stricte des données d'entrée
- Contrôle d'accès basé sur les rôles
- Protection contre les injections SQL

### Scalabilité
- Architecture modulaire pour faciliter l'extension
- Séparation des responsabilités (Repository, Service, Manager)
- Configuration externalisée

## Monitoring et Logs

Le système génère des logs pour :
- Ajout/suppression de membres
- Changements de rôles/statuts
- Sessions de travail
- Transactions importantes
- Erreurs et exceptions

## Maintenance

### Commandes d'Administration
- Nettoyage automatique des anciennes sessions
- Génération de rapports automatiques
- Sauvegarde des configurations

### Mise à Jour
Le système est conçu pour être facilement mis à jour sans perte de données grâce à :
- Migrations de base de données
- Compatibilité ascendante des configurations
- Architecture modulaire

## Support et Extension

### Ajout de Nouveaux Rôles
1. Modifier l'enum `StoreMemberRole`
2. Ajouter les permissions dans `store_members.yml`
3. Mettre à jour la base de données si nécessaire

### Nouvelles Fonctionnalités
Le système est conçu pour être facilement extensible :
- Nouveaux types de transactions
- Intégrations avec d'autres systèmes
- Nouveaux rapports et métriques

---

**Note**: Ce système est production-ready et optimisé pour la stabilité à long terme, la modularité et la performance. Il suit les meilleures pratiques Laravel pour faciliter l'intégration frontend.