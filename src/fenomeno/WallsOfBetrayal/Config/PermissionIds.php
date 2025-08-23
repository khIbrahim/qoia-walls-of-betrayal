<?php

namespace fenomeno\WallsOfBetrayal\Config;

final class PermissionIds
{

    public const FEED  = 'wob.command.feed';
    public const VAULT = 'wob.command.vault';
    public const VAULT_OTHER = 'wob.command.vault.other';
    public const BUILDER_GRACE = 'wob.abilities.builder_grace';
    public const GIVE_KIT = 'wob.command.givekit';
    public const SELL     = 'wob.command.sell';
    public const CRAFT    = 'wob.command.craft';
    public const CRAFT_OTHER = 'wob.command.craft.other';
    public const NICK = 'wob.command.nick';
    public const NICK_RESET = 'wob.command.nick.reset';
    public const NICK_LOG = 'wob.command.nick.log';
    public const MUTE = 'wob.command.mute';
    public const UNMUTE = 'wob.command.unmute';
    public const MUTE_LIST = 'wob.command.mutelist';
    public const BAN = 'wob.command.ban';
    public const UNBAN = 'wob.command.unban';
    public const TBAN = 'wob.command.tban';
    public const BAN_LIST = 'wob.command.banlist';
    public const REPORT = 'wob.command.report';
    public const SEE_REPORTS = 'wob.reports.see';
    public const REPORTS = 'wob.command.reports';
    public const KICK = 'wob.command.kick';
    public const STAFF_CHAT = 'wob.command.staffchat';
    public const HISTORY = 'wob.command.history';
    public const RANDOM_TP = 'wob.command.randomtp';
    public const INVSEE = 'wob.command.invsee';
    public const NPC = 'wob.command.npc';
    public const NPC_REMOVE = 'wob.command.npc.remove';
    public const NPC_CREATE = 'wob.command.npc.create';
    public const NPC_EDIT = 'wob.command.npc.edit';
    public const NPC_LIST = 'wob.command.npc.list';
    public const NPC_MOVE = 'wob.command.npc.move';
    public const NPC_TP = 'wob.command.npc.tp';

    public static function getVaultPerm(int $number): string
    {
        return "wob.command.vault.$number";
    }

}