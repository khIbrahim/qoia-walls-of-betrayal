<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

interface Statements
{

    public const INIT_PLAYERS                   = 'players.init';
    public const LOAD_PLAYER                    = 'players.load';
    public const INSERT_PLAYER                  = 'players.insert';
    public const SET_KINGDOM_PLAYER             = 'players.setKingdom';
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

}