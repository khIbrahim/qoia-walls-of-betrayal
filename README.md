# 🧱 Walls of Betrayal – Plugin de mode de jeu (QoiaMC)

> **Un mode de jeu exclusif Bedrock** développé pour le serveur Minecraft **QoiaMC**.  
Deux royaumes s'affrontent pendant 14 jours réels. Le quinzième jour, le mur tombe... et la trahison commence.

---

## 🎮 Concept du jeu

- Deux Royaumes :
  - 🔴 **Gor'Vok Clan** – Force brutale, armure forgée dans la lave
  - 🔵 **Thrag'Mar Legion** – Magie noire, furtivité, embuscades

- **14 jours de survie** : construire, farmer, se préparer
- **Jour 15** : le mur tombe. PvP activé. Les coéquipiers peuvent s'entretuer.
- Le dernier joueur ou royaume survivant **remporte la saison**

---

## 🧩 Architecture du plugin

| Module               | Rôle                                                                 |
|----------------------|----------------------------------------------------------------------|
| `WallsCore`          | Gestion des phases, minuterie, mur, scoreboard                       |
| `WallsKingdoms`      | Sélection des royaumes, attribution, changement, traîtres            |
| `WallsProgression`   | Suivi du farming collectif pour déverrouiller les kits               |
| `WallsAbilities`     | Capacités spéciales des royaumes avec cooldowns                      |
| `WallsCombat`        | Système PvP, trahison, bonus de backstab, détection                  |
| `WallsKitManager`    | Système complet de kits, cooldowns, conditions d'accès               |
| `WallsEconomy`       | Boutique, spawners, système d'XP, leaderboard                        |
| `WallsReset`         | Réinitialisation toutes les 2 semaines, sauvegarde des données       |
| `WallsMenus`         | Menus UI/UX : choix du royaume, boutique, kits, stats               |
| `WallsEnforcer`      | Systèmes anti-chest, restrictions, jail, alertes                     |

---

## 🗺️ Plan de développement

### ✅ PHASE 1 – Systèmes de base
- Système de phases (jours, chute du mur)
- Sélection des royaumes via menu
- Spawn personnalisé par royaume
- Scoreboard + couleurs d’équipe
- Gestion du mur central et de sa chute (particules, animation, PvP toggle)

### 🛠 PHASE 2 – Progression & Kits
- Suivi du farming collectif (blé, carottes, cochons, moutons)
- Déverrouillage ou blocage définitif des kits selon date limite
- Cooldown, animation d’obtention, menu des kits

### 💥 PHASE 3 – Capacités et PvP
- Capacité spéciale par royaume avec effets visuels/sonores
- Cooldown visible, items spéciaux ou commandes
- Suivi des trahisons, bonus de backstab, stats

### 💰 PHASE 4 – Boutique, Économie, Enchantements
- Système d’achat/vente
- Gestion d’XP et d’enchantements spécifiques à chaque royaume
- Shop visuel et interactif

### 🔄 PHASE 5 – Reset, Scores & Sécurité
- Réinitialisation automatique chaque 15 jours (wipe + leaderboard)
- Enforceur : anti-chest, restrictions, messages
- Bordures qui rétrécissent (après le mur)

---

## 🎨 UX/UI – Thème visuel

### 🔴 Gor'Vok Clan
- Couleur principale : `#b72b2b` (rouge lave)
- Ambiance : force, feu, brutalité
- Effets : flammes, sons graves, vibration

### 🔵 Thrag'Mar Legion
- Couleur principale : `#3344aa` (bleu nuit)
- Ambiance : magie, furtivité, mystère
- Effets : particules sombres, brume, chuchotements

---

## ⚙️ Fichiers de configuration (YAML)

- `kits.yml` : contenu des kits, cooldowns, conditions
- `kingdoms.yml` : infos de chaque royaume (nom, couleur, spawn, capacités)
- `grind.yml` : objectifs par jour
- `abilities.yml` : effets spéciaux par royaume
- `economy.yml` : boutique, prix d’achat/vente
- `phases.yml` : jours clés (wall, reset, shrink)
- `scoreboard.yml` : contenu du scoreboard
- `restrictions.yml` : règles anti-triche et punitions

---

## ✅ Tips Pro

- Fournir une API publique : `WallsAPI::getKingdom(Player $player)`
- Utiliser un logger (`WallsLogger`) pour événements importants
- Support du debug : `/wallsdebug` pour inspecter l’état interne
- Prévoir un système de traduction (`locales/fr.yml`)
- Ajouter un README clair et un Trello public si projet open-source

---

## 🚀 Démarrage conseillé

1. Créer `WallsMain` + architecture de dossiers
2. Implémenter `GamePhaseManager`
3. Implémenter `/choosekingdom`
4. Gérer les spawns par royaume
5. Ajouter gestion du mur et PvP toggle après 14 jours
6. Bonus : envoyer une démo vidéo de la chute du mur à ton supérieur

---

**Projet ambitieux, structuré, immersif.  
À coder proprement pour une longue durée de vie et des saisons rejouables.**
