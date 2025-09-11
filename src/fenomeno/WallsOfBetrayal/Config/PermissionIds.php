<?php

namespace fenomeno\WallsOfBetrayal\Config;

final class PermissionIds
{

    public const FEED                  = 'wob.command.feed';
    public const VAULT                 = 'wob.command.vault';
    public const VAULT_OTHER           = 'wob.command.vault.other';
    public const BUILDER_GRACE         = 'wob.abilities.builder_grace';
    public const GIVE_KIT              = 'wob.command.givekit';
    public const SELL                  = 'wob.command.sell';
    public const CRAFT                 = 'wob.command.craft';
    public const CRAFT_OTHER           = 'wob.command.craft.other';
    public const NICK                  = 'wob.command.nick';
    public const NICK_RESET            = 'wob.command.nick.reset';
    public const NICK_LOG              = 'wob.command.nick.log';
    public const MUTE                  = 'wob.command.mute';
    public const UNMUTE                = 'wob.command.unmute';
    public const MUTE_LIST             = 'wob.command.mutelist';
    public const BAN                   = 'wob.command.ban';
    public const UNBAN                 = 'wob.command.unban';
    public const TBAN                  = 'wob.command.tban';
    public const BAN_LIST              = 'wob.command.banlist';
    public const REPORT                = 'wob.command.report';
    public const SEE_REPORTS           = 'wob.reports.see';
    public const REPORTS               = 'wob.command.reports';
    public const KICK                  = 'wob.command.kick';
    public const STAFF_CHAT            = 'wob.command.staffchat';
    public const HISTORY               = 'wob.command.history';
    public const RANDOM_TP             = 'wob.command.randomtp';
    public const INVSEE                = 'wob.command.invsee';
    public const NPC                   = 'wob.command.npc';
    public const NPC_REMOVE            = 'wob.command.npc.remove';
    public const NPC_CREATE            = 'wob.command.npc.create';
    public const NPC_EDIT              = 'wob.command.npc.edit';
    public const NPC_LIST              = 'wob.command.npc.list';
    public const NPC_MOVE              = 'wob.command.npc.move';
    public const NPC_TP                = 'wob.command.npc.tp';
    public const FLOATING_TEXT         = 'wob.command.floatingtext';
    public const FLOATING_TEXT_CREATE  = 'wob.command.floatingtext.create';
    public const FLOATING_TEXT_DELETE  = 'wob.command.floatingtext.delete';
    public const FLOATING_TEXT_EDIT    = 'wob.command.floatingtext.edit';
    public const FLOATING_TEXT_LIST    = 'wob.command.floatingtext.list';
    public const NPC_LOAD              = 'wob.command.npc.load';
    public const NPC_CLEANUP           = 'wob.command.npc.cleanup';
    public const LOBBY                 = 'wob.command.lobby';
    public const LOBBY_OTHER           = 'wob.command.lobby.other';
    public const SET_LOBBY             = 'wob.command.setlobby';
    public const SPAWN                 = 'wob.command.spawn';
    public const SPAWN_OTHER           = 'wob.command.spawn.other';
    public const SET_SPAWN             = 'wob.command.setspawn';
    public const KINGDOM               = 'wob.command.kingdom';
    public const KINGDOM_SPAWN         = 'wob.command.kingdom.spawn';
    public const PORTAL                = 'wob.command.portal';
    public const BYPASS_LOBBY          = 'wob.bypass.lobby';
    public const SET_LOBBY_SETTING     = 'wob.command.setlobbysetting';
    public const KINGDOM_CONTRIBUTE    = 'wob.command.kingdom.contribute';
    public const KINGDOM_ABILITIES     = 'wob.command.kingdom.abilities';
    public const KINGDOM_ADD_XP        = 'wob.command.kingdom.addxp';
    public const KINGDOM_MANAGE        = 'wob.command.kingdom.manage';
    public const KINGDOM_SET_BORDERS   = 'wob.command.kingdom.setborders';
    public const KINGDOM_SET_SPAWN     = 'wob.command.kingdom.setspawn';
    public const KINGDOM_KICK          = 'wob.command.kingdom.kick';
    public const KINGDOM_BAN           = 'wob.command.kingdom.ban';
    public const KINGDOM_TOP           = 'wob.command.kingdom.top';
    public const KINGDOM_INFO          = 'wob.command.kingdom.info';
    public const KINGDOM_MAP           = 'wob.command.kingdom.map';
    public const KINGDOM_SPAWN_OTHER   = 'wob.command.kingdom.spawn.other';
    public const BYPASS_SPAWN_COOLDOWN = 'wob.bypass.spawn.cooldown';
    public const BYPASS_COMBAT_TAG     = 'wob.bypass.combat.tag';
    public const BYPASS_SEASON         = 'wob.bypass.season';

    public static function getVaultPerm(int $number): string
    {
        return "wob.command.vault.$number";
    }

    public static function getLobbyPerm(string $key): string
    {
        return 'wob.bypass.lobby' . $key;
    }

}