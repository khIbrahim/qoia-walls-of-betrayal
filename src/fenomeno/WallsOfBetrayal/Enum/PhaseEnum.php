<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum PhaseEnum: string
{
    // Phase d'attente initiale avant le début du jeu
    // Les joueurs peuvent se connecter, se préparer, choisir leur royaume
    case LOBBY = 'lobby';

    // Phase de préparation : les joueurs sont téléportés dans leurs bases,
    // ils peuvent s'équiper, organiser leur défense, se coordonner avec leur équipe
    case PREPARATION = 'preparation';

    // Phase active de jeu : les murs sont encore debout,
    // chaque équipe doit défendre sa base tout en récoltant ressources
    case GRIND = 'grind';

    // Phase finale : le mur tombe,
    // les joueurs peuvent s'affronter librement (PvP activé dans toutes les zones)
    case BATTLE = 'battle';

    // Phase de fin de partie : annonce des résultats, récompenses,
    // et retour au lobby ou déconnexion
    case END = 'end';

    // Phase d'interruption / pause, utilisée si le jeu doit être suspendu
    case PAUSE = 'pause';

    /**
     * Durée (en jours réels) de chaque phase pour la gestion du cycle complet.
     * Exemple indicatif, à ajuster selon ton gameplay et timing.
     */
    public function day(): int
    {
        return match($this) {
            self::LOBBY => 1,          // 1 day in lobby for players to join and prepare
            self::PREPARATION => 2,    // 2 days to gather resources and build defenses
            self::GRIND => 7,          // 7 days to farm, craft, strategize
            self::BATTLE => 4,         // 4 days of open PvP action
            self::END => 1,            // 1 day to announce results and reset
            self::PAUSE => 0           // no duration, paused state
        };
    }

    public function displayName(): string
    {
        return match($this) {
            self::LOBBY => "§7[§8Lobby§7]",
            self::PREPARATION => "§7[§6Preparation§7]",
            self::GRIND => "§7[§2Cold War§7]",
            self::BATTLE => "§7[§cBetrayal§7]",
            self::END => "§7[§5End§7]",
            self::PAUSE => "§7[§9Paused§7]",
        };
    }

}