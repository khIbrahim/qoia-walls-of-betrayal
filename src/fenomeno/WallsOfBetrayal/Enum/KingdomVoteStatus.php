<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum KingdomVoteStatus: string
{

    case Active = 'active';
    case Passed = 'passed';
    case Failed = 'failed';
    case Expired = 'expired';
}