<?php

namespace fenomeno\WallsOfBetrayal\Utils\Messages;

final class MessagesIds
{
    // Commande Balance
    public const BALANCE_INFO = 'economy.balance.info';
    public const BALANCE_OTHER_INFO = 'economy.balance.info_other';
    public const BALANCE_ACCOUNT_NONEXISTENT = 'economy.errors.account.nonexistent';
    public const BALANCE_ERR_DATABASE = 'economy.errors.database';

    // Commande Pay
    public const BALANCE_PAY = 'economy.balance.pay'; // Message lorsque l'on effectue un paiement
    public const BALANCE_PAY_RECEIVE = 'economy.balance.pay_receive'; // Message lorsque l'on reçoit un paiement
    public const BALANCE_ERR_PAY_SELF = 'economy.errors.pay.self'; // Erreur si on essaie de se payer soi-même
    public const BALANCE_ERR_AMOUNT_INVALID = 'economy.errors.amount.invalid'; // Erreur pour un montant invalide
    public const BALANCE_ERR_AMOUNT_SMALL = 'economy.errors.amount.small'; // Erreur pour un montant trop petit
    public const BALANCE_ERR_AMOUNT_LARGE = 'economy.errors.amount.large'; // Erreur pour un montant trop élevé
    public const BALANCE_ERR_ACCOUNT_INSUFFICIENT = 'economy.errors.account.insufficient'; // Erreur fonds insuffisants

    public const RICH_HEADER = 'economy.rich.header'; // Message d'en-tête pour le classement des riches
    public const RICH_ENTRY = 'economy.rich.entry'; // Entrée individuelle dans le classement
    public const RICH_FOOTER = 'economy.rich.footer'; // Message de bas de page du classement
    public const ERROR_RICH_NO_RECORDS = 'economy.errors.rich.no_records'; // Erreur lorsqu'il n'y a pas de records à afficher
    public const BALANCE_ADD = 'economy.balance.add';
    public const BALANCE_SET = 'economy.balance.set';
    public const BALANCE_REMOVE = 'economy.balance.remove';
    public const BALANCE_ERR_INSUFFICIENT_FUNDS = 'economy.errors.account.insufficient';
    public const BALANCE_ACCOUNT_MISSING_DATA = 'economy.errors.account.missingData';
    public const ERROR = 'common.logicError';
    public const BALANCE_SAME_BALANCE = 'economy.errors.amount.same';
    public const NOT_PLAYER = 'common.notPlayer';
    public const PLAYER_NOT_FOUND = 'common.playerNotFound';
    public const HUNGER_RESTORED = 'commands.feed.restored';
    public const HUNGER_RESTORED_FOR_PLAYER = 'commands.feed.restoredForPlayer';
    public const ROLE_NOT_FOUND = "roles.errors.role.notFound";
    public const ALREADY_HAS_ROLE = "roles.errors.role.alreadyHasRole";
    public const ROLE_SET = 'roles.role.set';
    public const ROLE_PLAYER_SET = 'roles.role.playerSet';
    public const PLAYER_ROLE_NOT_FOUND = 'roles.errors.rolePlayer.notFound';
    public const ROLES_EXPIRED_TO_DEFAULT = 'roles.role.expiredToDefault';
    public const ALREADY_HAS_PERMISSION = 'roles.errors.permission.already_has';
    public const PERMISSION_PLAYER_SET = 'roles.permission.added';
    public const PERMISSION_PLAYER_REMOVED = 'roles.permission.removed';
    public const PLAYER_DONT_HAVE_PERMISSION = 'roles.errors.permission.notGranted';
    public const PLAYER_ROLE_INFO_SELF = 'roles.info.self';
    public const PLAYER_ROLE_INFO_OTHER = 'roles.info.other';
    public const SUBROLE_PLAYER_SET = 'roles.subrole.added';
    public const SUBROLE_SET = 'roles.subrole.set';
    public const SUBROLE_REMOVED = 'roles.subrole.removed';
    public const PLAYER_DONT_HAVE_SUBROLE = 'roles.errors.subrole.notOwned';
    public const ALREADY_HAS_SUBROLE = 'roles.errors.subrole.alreadyHas';
    public const SUBROLE_NOT_FOUND = 'roles.errors.subrole.notFound';
    public const VAULT_NUMBER_TOO_HIGH = 'commands.vault.errors.numberTooHigh';
    public const VAULT_NO_PERMISSION = 'commands.vault.errors.noPermission';
    public const VAULT_NUMBER_TOO_LOW = 'commands.vault.errors.numberTooLow';
    public const VAULT_OPENING = 'commands.vault.opening';
    public const VAULT_OPENED = 'commands.vault.opened';
    public const VAULT_CLOSED = 'commands.vault.closed';
    public const VAULT_NO_PERMISSION_OTHER = 'commands.vault.errors.noPermissionOther';
    public const VAULT_NOT_FOUND = 'commands.vault.errors.notFound';
    public const ITEM_LOCKED = 'common.item.locked';
    public const UNKNOWN_KIT = 'kits.unknown';
    public const KIT_GIVEN = 'kits.given';
    const SPAWNER_ADDED = 'commands.spawner.added';
    const SPAWNER_NOT_FOUND = 'commands.spawner.notFound';
    public const INVALID_NUMBER = 'common.invalidNumber';
    public const INVALID_SELL_ARGUMENT = 'commands.sell.errors.invalidArgument';
    public const SELL_NOTHING_TO_SELL = 'commands.sell.errors.nothingToSell';
    public const SELL_ALL_SUCCESS = 'commands.sell.all.success';
    public const ITEM_NOT_SELLABLE = 'commands.sell.errors.itemNotSellable';
    public const SELL_LEFTOVER = 'commands.sell.hand.leftover';
    public const SHOP_SOLD = 'shop.sold';
    public const NOT_IN_KINGDOM = 'kingdoms.errors.notInKingdom';
    public const ENCHANTING_TABLE_NO_ENCHANTMENTS_FOR_KINGDOM = 'kingdoms.enchantingTable.errors.noEnchantments';
    public const ENCHANTING_TABLE_TITLE = 'kingdoms.enchantingTable.default-menu.title';
    public const ENCHANTING_TABLE_TEXT = 'kingdoms.enchantingTable.default-menu.text';
    public const ENCHANTING_TABLE_BUTTON = 'kingdoms.enchantingTable.default-menu.button';
    public const ENCHANTING_TABLE_NOT_ENOUGH_RESOURCES = 'kingdoms.enchantingTable.errors.notEnoughResources';
    public const ENCHANTING_TABLE_INVALID_ENCHANTMENT = 'kingdoms.enchantingTable.errors.invalidEnchantment';
    public const PLAYER_KILL = 'common.onKill';
    public const PLAYER_NOT_LOADED = 'common.playerNotLoaded';
    public const PLAYER_STATS = 'commands.stats.playerStats';

}
