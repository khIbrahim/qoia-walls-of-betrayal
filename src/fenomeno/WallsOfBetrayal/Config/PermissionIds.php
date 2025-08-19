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

    public static function getVaultPerm(int $number): string
    {
        return "wob.command.vault.$number";
    }

}