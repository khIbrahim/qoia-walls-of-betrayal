<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

interface Statements
{

    public const INIT_PLAYERS              = 'players.init';
    public const LOAD_PLAYER               = 'players.load';
    public const INSERT_PLAYER             = 'players.insert';
    public const SET_KINGDOM_PLAYER        = 'players.setKingdom';
    public const LOAD_KIT_REQUIREMENT      = 'kit_requirements.getByKingdomAndKit';
    public const INSERT_KIT_REQUIREMENT    = 'kit_requirements.insert';
    public const INCREMENT_KIT_REQUIREMENT = 'kit_requirements.increment';
    public const INIT_KIT_REQUIREMENT      = 'kit_requirements.init';
    public const UPDATE_PLAYER_ABILITIES   = 'players.updateAbilities';

}