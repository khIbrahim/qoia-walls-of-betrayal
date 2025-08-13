<?php

namespace fenomeno\WallsOfBetrayal\Language;

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
    public const NO_PLAYER = 'common.notPlayer';
    public const PLAYER_NOT_FOUND = 'common.playerNotFound';
    public const HUNGER_RESTORED = 'commands.feed.restored';
    public const HUNGER_RESTORED_FOR_PLAYER = 'commands.feed.restoredForPlayer';

}
