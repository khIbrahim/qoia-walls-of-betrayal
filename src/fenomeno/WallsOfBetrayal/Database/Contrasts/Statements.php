<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

interface Statements
{

    public const INIT_PLAYERS        = 'players.init';
    public const LOAD_PLAYER         = 'players.load';
    public const INSERT_PLAYER       = 'players.insert';
    public const SET_KINGDOM_PLAYER  = 'players.setKingdom';

}