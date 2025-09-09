<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

interface Statements
{

    public const INIT_PLAYERS                   = 'players.init';
    public const LOAD_PLAYER                    = 'players.load';
    public const LOAD_PLAYER_BY_NAME            = 'players.loadByName';
    public const INSERT_PLAYER                  = 'players.insert';
    public const SET_KINGDOM_PLAYER             = 'players.setKingdom';
    public const GET_KINGDOMS_PLAYERS_COUNT = 'players.getKingdomPlayersCount';

    public const LOAD_KIT_REQUIREMENT           = 'kit_requirements.getByKingdomAndKit';
    public const INSERT_KIT_REQUIREMENT         = 'kit_requirements.insert';
    public const INCREMENT_KIT_REQUIREMENT      = 'kit_requirements.increment';
    public const INIT_KIT_REQUIREMENT           = 'kit_requirements.init';

    public const UPDATE_PLAYER_ABILITIES        = 'players.updateAbilities';

    public const INIT_COOLDOWNS                 = 'cooldowns.init';
    public const GET_ACTIVE_COOLDOWNS           = 'cooldowns.getActive';
    public const GET_PLAYER_COOLDOWNS_COOLDOWNS = 'cooldowns.getPlayerCooldowns';
    public const UPSERT_COOLDOWN                = 'cooldowns.upsert';
    public const REMOVE_COOLDOWN                = 'cooldowns.remove';
    public const CLEANUP_EXPIRED_COOLDOWNS      = 'cooldowns.cleanupExpired';
    public const GET_PLAYER_COOLDOWN            = 'cooldowns.getPlayerSpecificCooldown';

    public const INIT_ECONOMY                   = 'economy.init';
    public const GET_ECONOMY                    = 'economy.get';
    public const INSERT_ECONOMY                 = 'economy.insert';
    public const ADD_ECONOMY                    = 'economy.add';
    public const SUBTRACT_ECONOMY               = 'economy.subtract';
    public const TRANSFER_ECONOMY               = 'economy.transfer';
    public const TOP_ECONOMY                    = 'economy.top';
    public const SET_ECONOMY                    = 'economy.set';
    public const TRANSFER_BEGIN                 = 'economy.transfer.begin';
    public const TRANSFER_COMMIT                = 'economy.transfer.commit';
    public const TRANSFER_ROLLBACK              = 'economy.transfer.rollback';
    public const TRANSFER_DEBIT_SENDER          = 'economy.transfer.debitSender';
    public const CREDIT_RECEIVER                = 'economy.transfer.creditReceiver';

    public const INIT_ROLES                     = 'roles.init';
    public const GET_PLAYER_ROLE                = 'roles.get';
    public const INSERT_PLAYER_ROLE             = 'roles.assign';
    public const UPDATE_PLAYER_ROLE_ROLE        = 'roles.updateRole';
    public const GET_PLAYER_PERMISSIONS         = 'roles.getPermissions';
    public const UPDATE_PLAYER_PERMISSIONS      = 'roles.updatePermissions';
    public const GET_PLAYER_SUBROLES            = 'roles.getSubRoles';
    public const UPDATE_PLAYER_SUBROLES         = 'roles.updateSubRoles';

    public const INIT_VAULT                     = 'vaults.init';
    public const OPEN_VAULT                     = 'vaults.open';
    public const CLOSE_VAULT                    = 'vaults.close';

    public const INIT_KINGDOMS                  = 'kingdoms.init';
    public const LOAD_KINGDOM                   = 'kingdoms.get';
    public const INSERT_KINGDOM                 = 'kingdoms.insert';
    public const INCREMENT_KILLS                = 'players.incrementKills';
    public const INCREMENT_DEATHS               = 'players.incrementDeaths';
    public const INCREMENT_KINGDOM_KILLS        = 'kingdoms.incrementKills';
    public const INCREMENT_KINGDOM_DEATHS       = 'kingdoms.incrementDeaths';
    public const UPDATE_KINGDOM_SPAWN           = 'kingdoms.updateSpawn';
    public const UPDATE_KINGDOM_RALLY_POINT = 'kingdoms.updateRallyPoint';
    
    public const INIT_MUTE    = 'mute.init';
    public const MUTE_GET     = 'mute.get';
    public const MUTE_CREATE  = 'mute.create';
    public const MUTE_DELETE  = 'mute.delete';
    
    public const INIT_HISTORY   = "history.init";
    public const HISTORY_ADD    = "history.add";
    public const HISTORY_GET    = "history.get";

    public const INIT_BAN       = "ban.init";
    public const BAN_ADD        = "ban.add";
    public const BAN_REMOVE     = "ban.remove";
    public const BAN_GETALL     = "ban.getAll";

    public const INIT_JAIL      = "jail.init";
    public const JAIL_GET       = "jail.get";
    public const JAIL_CREATE    = "jail.create";
    public const JAIL_DELETE    = "jail.delete";
    public const JAIL_UPDATE    = 'jail.update';

    public const INIT_REPORT    = "report.init";
    public const REPORT_CREATE  = "report.create";
    public const REPORT_GET     = "report.get";
    public const REPORT_DELETE  = "report.delete";
    public const REPORT_ARCHIVE = "report.archive";

    public const INIT_FLOATING_TEXT = 'floatingText.init';
    public const LOAD_FLOATING_TEXTS = 'floatingText.load';
    public const CREATE_FLOATING_TEXT = 'floatingText.create';
    public const REMOVE_FLOATING_TEXT = 'floatingText.remove';
    public const UPDATE_FLOATING_TEXT = 'floatingText.updateText';

    public const INIT_NPC     = 'npc.init';
    public const CREATE_NPC   = 'npc.create';
    public const UPDATE_NPC   = 'npc.update';
    public const DELETE_NPC   = 'npc.delete';
    public const LOAD_ALL_NPC = 'npc.loadAll';

    public const INIT_SERVER             = 'server.init';
    public const LOAD_SERVER             = 'server.load';
    public const INSERT_SERVER           = 'server.insert';
    public const UPDATE_LOBBY_LOC_SERVER = 'server.updateLobbyLoc';
    public const ADD_KINGDOM_XP = 'kingdoms.addXP';
    const ADD_KINGDOM_BALANCE = 'kingdoms.addBalance';

    public const INIT_KINGDOMS_BOUNTY = 'kingdom_bounties.init';
    public const CREATE_KINGDOMS_BOUNTY = 'kingdom_bounties.create';
    public const GET_ALL_ACTIVE_KINGDOMS_BOUNTY = 'kingdom_bounties.getAllActive';
    public const DEACTIVATE_KINGDOM_BOUNTY = 'kingdom_bounties.deactivate';

    public const INIT_KINGDOM_SANCTIONS = 'kingdom_bans.init';
    public const CREATE_KINGDOM_SANCTION = 'kingdom_bans.create';
    public const DEACTIVATE_KINGDOM_SANCTION = 'kingdom_bans.deactivate';
    public const IS_PLAYER_SANCTIONED = 'kingdom_bans.isBanned';
    public const LOAD_ACTIVE_SANCTIONS = 'kingdom_bans.getActive';
    public const GET_PLAYER_SANCTION_HISTORY = 'kingdom_bans.getPlayerHistory';

    // Kingdom votes
    public const LOAD_KINGDOM_VOTES           = 'kingdom_votes.load';
    public const INIT_KINGDOM_VOTES           = 'kingdom_votes.init';
    public const CREATE_KINGDOM_VOTE          = 'kingdom_votes.create';
    public const CAST_KINGDOM_VOTE            = 'kingdom_votes.vote';
    public const UPDATE_KINGDOM_VOTE_STATUS   = 'kingdom_votes.updateStatus';
    public const INIT_KINGDOM_VOTE_VOTES      = 'kingdom_vote_votes.init';
    public const COUNT_KINGDOM_VOTE_VOTES     = 'kingdom_vote_votes.count';
    public const GET_KINGDOM_VOTE             = 'kingdom_votes.get';
    public const GET_KINGDOM_VOTER_CHOICE     = 'kingdom_vote_votes.getVoterChoice';
    public const UPDATE_KINGDOM_VOTE_VOTES    = 'kingdom_votes.updateVotes';
    public const DELETE_EXPIRED_KINGDOM_VOTES = 'kingdom_votes.deleteExpired';
    public const UPDATE_KINGDOM_BORDERS       = 'kingdoms.updateBorders';

    public const INIT_PLAYER_INVENTORIES = 'player_inventories.init';
    public const LOAD_PLAYER_INVENTORIES   = 'player_inventories.load';
    public const SAVE_PLAYER_INVENTORIES = 'player_inventories.save';

}