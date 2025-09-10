<?php

namespace fenomeno\WallsOfBetrayal\Enum;

use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;

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

    public function wallState(): WallStateEnum
    {
        return match ($this) {
            self::LOBBY, self::PREPARATION, self::PAUSE, self::GRIND => WallStateEnum::INTACT,
            self::BATTLE, self::END                                  => WallStateEnum::BREACHED,
        };
    }

    public function next(): PhaseEnum
    {
        return match($this){
            self::LOBBY => self::PREPARATION,
            self::PREPARATION => self::GRIND,
            self::GRIND => self::BATTLE,
            self::BATTLE => self::END,
            self::END => self::PAUSE,
            self::PAUSE => self::LOBBY
        };
    }

    public function getBroadcastMessage(): string
    {
        return match($this) {
            self::LOBBY       => MessagesUtils::getMessage(MessagesIds::PHASE_LOBBY_BROADCAST),
            self::PREPARATION => MessagesUtils::getMessage(MessagesIds::PHASE_PREPARATION_BROADCAST),
            self::GRIND       => MessagesUtils::getMessage(MessagesIds::PHASE_GRIND_BROADCAST),
            self::BATTLE      => MessagesUtils::getMessage(MessagesIds::PHASE_BATTLE_BROADCAST),
            self::END         => MessagesUtils::getMessage(MessagesIds::PHASE_END_BROADCAST),
            self::PAUSE       => MessagesUtils::getMessage(MessagesIds::PHASE_PAUSE_BROADCAST),
        };
    }

}