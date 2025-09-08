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
    public const BALANCE_PAY = 'economy.balance.pay';
    public const BALANCE_PAY_RECEIVE = 'economy.balance.pay_receive';
    public const BALANCE_ERR_PAY_SELF = 'economy.errors.pay.self';
    public const BALANCE_ERR_AMOUNT_INVALID = 'economy.errors.amount.invalid';
    public const BALANCE_ERR_AMOUNT_SMALL = 'economy.errors.amount.small';
    public const BALANCE_ERR_AMOUNT_LARGE = 'economy.errors.amount.large';
    public const BALANCE_ERR_ACCOUNT_INSUFFICIENT = 'economy.errors.account.insufficient';

    public const RICH_HEADER = 'economy.rich.header';
    public const RICH_ENTRY = 'economy.rich.entry';
    public const RICH_FOOTER = 'economy.rich.footer';
    public const ERROR_RICH_NO_RECORDS = 'economy.errors.rich.no_records';
    public const BALANCE_ADD = 'economy.balance.add';
    public const BALANCE_SET = 'economy.balance.set';
    public const BALANCE_REMOVE = 'economy.balance.remove';
    public const BALANCE_ERR_INSUFFICIENT_FUNDS = 'economy.errors.account.insufficient';
    public const BALANCE_ACCOUNT_MISSING_DATA = 'economy.errors.account.missingData';
    public const GENERAL_BACK = 'common.back';
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
    public const NO_ITEM_IN_HAND = 'common.noItemInHand';
    public const ENCHANTMENT_NOT_AVAILABLE = 'kingdoms.enchantments.errors.notAvailable';
    public const ENCHANTING_TABLE_SUCCESS = 'kingdoms.enchantingTable.success';
    public const CRAFTING_TABLE_OPENED = 'commands.craft.opened';
    public const NO_PERMISSION = 'common.noPermission';
    public const NICK_CANCELLED = 'commands.nick.cancelled';
    public const NICK_SUCCESS = 'commands.nick.success';
    public const NICK_RESET = 'commands.nick.reset';
    public const NICK_REJOIN = 'commands.nick.onRejoin';
    public const NICK_ALREADY_SET = 'commands.nick.errors.alreadySet';
    public const NICK_EMPTY = 'commands.nick.errors.empty';
    public const NICK_ALREADY_USED = 'commands.nick.errors.alreadyUsed';
    public const NICK_INVALID = 'commands.nick.errors.invalid';
    public const NICK_NOT_SET = 'commands.nick.errors.notSet';
    public const NICK_LOG_ENTRY = 'commands.nick.log.entry';
    public const NICK_LOG_EMPTY = 'commands.nick.log.empty';
    public const NICK_LOG_LIST_HEADER = 'commands.nick.log.listHeader';
    public const ENCHANTING_TABLE_NOT_ENOUGH_XP = 'kingdoms.enchantingTable.errors.notEnoughXp';
    public const BROADCAST_MUTE = 'punishment.mute.broadcast';
    public const ALREADY_MUTED = 'punishment.mute.errors.alreadyMuted';
    public const DEFAULT_REASON = 'punishment.defaultReason';
    public const MUTE_TARGET_MUTED = 'punishment.mute.targetMuted';
    public const NOT_MUTED = 'punishment.mute.errors.notMuted';
    public const BROADCAST_UNMUTE = 'punishment.unmute.broadcast';
    public const UNMUTE_SUCCESS = 'punishment.unmute.success';
    public const MUTE_LIST_EMPTY = 'punishment.muteList.empty';
    public const MUTE_LIST_HEADER = 'punishment.muteList.header';
    public const MUTE_LIST_ENTRY = 'punishment.muteList.entry';
    public const MUTE_MUTED = 'punishment.mute.muted';
    public const NO_PUNISHMENT_TYPE_HISTORY = 'punishment.history.noPunishmentTypeHistory';
    public const ALREADY_BANNED = 'punishment.ban.errors.alreadyBanned';
    public const BROADCAST_BAN = 'punishment.ban.broadcast';
    public const BAN_TARGET_BANNED = 'punishment.ban.targetBanned';
    public const BAN_TEMP_SCREEN_MESSAGE = 'punishment.ban.screenMessageTemp';
    public const BAN_PERM_SCREEN_MESSAGE = 'punishment.ban.screenMessagePerm';
    public const BROADCAST_UNBAN = 'punishment.unban.broadcast';
    public const UNBAN_SUCCESS = 'punishment.unban.success';
    public const NOT_BANNED = 'punishment.unban.errors.notBanned';
    public const BAN_LIST_EMPTY = 'punishment.banlist.empty';
    public const INVALID_PAGE = 'common.invalidPage';
    public const PAGE_NOT_FOUND = 'common.pageNotFound';
    public const BAN_LIST_HEADER = 'punishment.banlist.header';
    public const BAN_LIST_ENTRY = 'punishment.banlist.entry';
    public const BROADCAST_REPORT = 'punishment.report.broadcast';
    public const ALREADY_REPORTED = 'punishment.report.errors.alreadyReported';
    public const REPORT_SUCCESS = 'punishment.report.success';
    public const REPORTS_EMPTY = 'punishment.report.reportList.empty';
    public const REPORTS_HEADER = 'punishment.report.reportList.header';
    public const REPORT_DETAILS = 'punishment.report.reportList.details';
    public const REPORT_REMOVED = 'punishment.report.reportList.removed';
    public const REPORT_NOT_FOUND = 'punishment.report.errors.notFound';
    public const KICK_SCREEN_MESSAGE = 'punishment.kick.screenMessage';
    public const ENTER_STAFF_MOD = 'commands.staffmod.enter';
    public const LEAVE_STAFF_MOD = 'commands.staffmod.leave';
    public const BROADCAST_ENTER_STAFF_CHAT = 'commands.staffchat.broadcastJoin';
    public const BROADCAST_LEAVE_STAFF_CHAT = 'commands.staffchat.broadcastLeave';
    public const FROZEN = 'commands.freeze.frozen';
    public const NO_COMMAND_STAFF_MOD_ITEM = 'commands.staffmod.errors.noItemCommand';
    public const STAFF_CHAT_FORMAT = 'commands.staffchat.format';
    public const VANISH_ENABLED = 'commands.vanish.enabled';
    public const VANISH_DISABLED = 'commands.vanish.disabled';
    public const FREEZE_ENABLED_ON_PLAYER = 'commands.freeze.enabledOnPlayer';
    public const FREEZE_DISABLED_ON_PLAYER = 'commands.freeze.disabledOnPlayer';
    public const PUNISHMENT_HISTORY_ENTRY = 'punishment.history.entry';
    public const SERVER_EMPTY = 'common.serverEmpty';
    public const NO_OTHER_PLAYERS = 'common.noOtherPlayers';
    public const TELEPORTED_TO_PLAYER = 'commands.randomTp.success';
    public const INVSEE_UPDATED = 'commands.invsee.updated';
    public const NPC_NOT_SET = 'npc.errors.notSet';
    public const NPC_CREATE_MENU_TITLE = 'npc.create.menu.title';
    public const NPC_CREATE_MENU_NAME_INPUT = 'npc.create.menu.name-input';
    public const NPC_CREATE_MENU_COMMAND_INPUT = 'npc.create.menu.command-input';
    public const NPC_CREATE_MENU_COMMAND_HIDDEN_INPUT = 'npc.create.menu.command-input-hidden';
    public const NPC_CREATE_MENU_NAME_EMPTY = 'npc.create.menu.errors.emptyName';
    public const NPC_CREATE_MENU_COMMAND_EMPTY = 'npc.create.menu.errors.emptyCommand';
    public const NPC_CREATE_MENU_SUCCESS = 'npc.create.success';
    public const NPC_REMOVED = 'npc.removed';
    public const NPC_ALREADY_EXISTS = 'npc.errors.alreadyExists';
    public const NPC_NOT_FOUND = 'npc.errors.notFound';
    public const NPC_MOVED_SUCCESS = 'npc.move.success';
    public const TELEPORTED_TO_NPC = 'npc.tp.success';
    public const NPC_EDIT_MENU_TITLE = 'npc.edit.menu.title';
    public const NPC_EDIT_MENU_NAME_INPUT = 'npc.edit.menu.name-input';
    public const NPC_EDIT_MENU_COMMAND_INPUT = 'npc.edit.menu.command-input';
    public const NPC_EDIT_MENU_SKIN_TOGGLE = 'npc.edit.menu.skin-toggle';
    public const NPC_EDITED = 'npc.edit.success';
    public const NPC_LIST_MENU_TITLE = 'npc.list.menu.title';
    public const NPC_LIST_MENU_TEXT = 'npc.list.menu.text';
    public const NPC_LIST_MENU_BUTTON = 'npc.list.menu.button';
    public const NPC_ACTIONS_MENU_TITLE = 'npc.actions.menu.title';
    public const NPC_ACTIONS_MENU_TEXT = 'npc.actions.menu.text';
    public const NPC_ACTIONS_MENU_TP_BUTTON = 'npc.actions.menu.teleport-button';
    public const NPC_ACTIONS_MENU_EDIT_BUTTON = 'npc.actions.menu.edit-button';
    public const NPC_ACTIONS_MENU_MOVE_BUTTON = 'npc.actions.menu.move-button';
    public const NPC_ACTIONS_MENU_REMOVE_BUTTON = 'npc.actions.menu.remove-button';
    public const ALREADY_IN_KINGDOM = 'kingdoms.errors.alreadyInKingdom';
    public const UNKNOWN_KINGDOM = 'kingdoms.errors.unknownKingdom';
    public const NPC_COOLDOWN = 'npc.cooldown';
    public const NPC_CREATE_MENU_COOLDOWN_INPUT = 'npc.create.menu.cooldown-slider';
    public const NPC_EDIT_MENU_COOLDOWN_INPUT = 'npc.edit.menu.cooldown-slider';
    public const FLOATING_TEXT_MISSING_LINE = 'floatingTexts.errors.noLines';
    public const FLOATING_TEXT_CREATE_SUCCESS = 'floatingTexts.create.success';
    public const FLOATING_TEXT_ALREADY_EXISTS = 'floatingTexts.errors.alreadyExists';
    public const FLOATING_TEXT_REMOVE_SUCCESS = 'floatingTexts.remove.success';
    public const UNKNOWN_FLOATING_TEXT = 'floatingTexts.errors.unknown';
    public const FLOATING_TEXT_EDIT_MENU_TITLE = 'floatingTexts.edit.menu.title';
    public const FLOATING_TEXT_EDIT_MENU_CURRENT_TEXT = 'floatingTexts.edit.menu.current-text-label';
    public const FLOATING_TEXT_EDIT_MENU_ADD_LINE = 'floatingTexts.edit.menu.add-line';
    public const FLOATING_TEXT_EDIT_MENU_DELETE_LINE = 'floatingTexts.edit.menu.delete-line';
    public const FLOATING_TEXT_EDIT_MENU_FINISH = 'floatingTexts.edit.menu.finish';
    public const FLOATING_TEXT_DELETED_LAST_LINE = 'floatingTexts.edit.removedLastLine';
    public const FLOATING_TEXT_EDIT_SUCCESS = 'floatingTexts.edit.success';
    public const LOBBY_SUCCESS_SELF = 'commands.lobby.self';
    public const LOBBY_SUCCESS_OTHER = 'commands.lobby.other';
    public const SET_LOBBY_SUCCESS = 'commands.set-lobby.success';
    public const SESSION_NOT_LOADED = 'common.playerNotLoaded';
    public const SPAWN_NO_KINGDOM = 'commands.spawn.errors.noKingdom.self';
    public const SPAWN_NO_KINGDOM_OTHER = 'commands.spawn.errors.noKingdom.other';
    public const SPAWN_NO_KINGDOM_SPAWN = 'commands.spawn.errors.noKingdomSpawn.self';
    public const SPAWN_NO_KINGDOM_SPAWN_OTHER = 'commands.spawn.errors.noKingdomSpawn.other';
    public const SPAWN_SUCCESS_SELF = 'commands.spawn.success.self';
    public const SPAWN_SUCCESS_OTHER = 'commands.spawn.success.other';
    public const SET_SPAWN_SUCCESS = 'commands.set-spawn.success';
    public const NO_PORTAL = 'commands.portal.errors.noPortal';
    public const PORTAL_SUCCESS = 'commands.portal.success';
    public const SET_LOBBY_SETTING_SUCCESS = 'commands.set-lobby-setting.success';
    public const PORTAL_REMOVE_SUCCESS = 'commands.portal.remove.success';
    public const UNSTABLE = 'common.unstable';
    public const KINGDOMS_INFO_HEADER = 'kingdoms.info.header';
    public const KINGDOMS_INFO_DESCRIPTION = 'kingdoms.info.description';
    public const KINGDOMS_INFO_STATS = 'kingdoms.info.stats';
    public const KINGDOMS_INFO_XP = 'kingdoms.info.xp';
    public const KINGDOMS_INFO_BALANCE = 'kingdoms.info.balance';
    public const KINGDOMS_INFO_MEMBERS = 'kingdoms.info.members';
    public const KINGDOMS_INFO_ABILITIES = 'kingdoms.info.abilities';

    // Kingdom Commands Messages
    public const KINGDOMS_SPAWN_SUCCESS = 'kingdoms.spawn.success';
    public const KINGDOMS_SPAWN_NO_SPAWN = 'kingdoms.spawn.noSpawn';
    public const KINGDOMS_SPAWN_NO_KINGDOM = 'kingdoms.spawn.noKingdom';

    public const KINGDOMS_TOP_HEADER = 'kingdoms.top.header';
    public const KINGDOMS_TOP_ENTRY = 'kingdoms.top.entry';
    public const KINGDOMS_TOP_FOOTER = 'kingdoms.top.footer';

    public const KINGDOMS_MAP_HEADER = 'kingdoms.map.header';
    public const KINGDOMS_MAP_TERRITORY = 'kingdoms.map.territory';
    public const KINGDOMS_MAP_CLAIMS = 'kingdoms.map.claims';
    public const KINGDOMS_MAP_BORDERS = 'kingdoms.map.borders';

    public const KINGDOMS_CONTRIBUTE_USAGE = 'kingdoms.contribute.usage';
    public const KINGDOMS_CONTRIBUTE_SUCCESS = 'kingdoms.contribute.success';
    public const KINGDOMS_CONTRIBUTE_INSUFFICIENT = 'kingdoms.contribute.insufficient';
    public const KINGDOMS_CONTRIBUTE_INVALID_TYPE = 'kingdoms.contribute.invalidType';

    public const KINGDOMS_ABILITIES_HEADER = 'kingdoms.abilities.header';
    public const KINGDOMS_ABILITIES_ABILITY = 'kingdoms.abilities.ability';
    public const KINGDOMS_ABILITIES_COOLDOWN = 'kingdoms.abilities.cooldown';
    public const KINGDOMS_ABILITIES_NO_ABILITIES = 'kingdoms.abilities.noAbilities';

    public const KINGDOMS_KICK_NOT_MEMBER = 'kingdoms.kick.notMember';
    public const KINGDOMS_KICK_SELF = 'kingdoms.kick.self';
    public const KINGDOMS_KICK_CONFIRM_HEADER = 'kingdoms.kick.confirm.header';
    public const KINGDOMS_KICK_CONFIRM_CONTENT = 'kingdoms.kick.confirm.content';

    public const KINGDOMS_VOTE_ALREADY_VOTED = 'kingdoms.vote.alreadyVoted';
    public const KINGDOMS_VOTE_SUCCESS = 'kingdoms.vote.success';
    public const KINGDOMS_VOTE_INFO = 'kingdoms.vote.voteInfo';
    // High-level vote messages
    public const KINGDOMS_NO_VOTES = 'kingdoms.vote.errors.noVote';
    public const KINGDOMS_VOTE_NOT_FOUND = 'kingdoms.vote.errors.notFound';
    public const KINGDOMS_VOTE_STATISTICS_UNAVAILABLE = 'kingdoms.vote.errors.statisticsUnavailable';
    public const KINGDOMS_VOTE_ALREADY_VOTED_SAME = 'kingdoms.vote.errors.alreadyVotedSame';

// Menu (Kick) header

// Menu fields
    public const KINGDOMS_VOTE_MENU_FIELD_TARGET = 'kingdoms.vote.menu.fields.target';
    public const KINGDOMS_VOTE_MENU_FIELD_REASON = 'kingdoms.vote.menu.fields.reason';
    public const KINGDOMS_VOTE_MENU_FIELD_PROPOSED_BY = 'kingdoms.vote.menu.fields.proposedBy';
    public const KINGDOMS_VOTE_MENU_FIELD_CREATED = 'kingdoms.vote.menu.fields.created';
    public const KINGDOMS_VOTE_MENU_FIELD_EXPIRES = 'kingdoms.vote.menu.fields.expires';
    public const KINGDOMS_VOTE_MENU_FIELD_TIMELEFT = 'kingdoms.vote.menu.fields.timeLeft';
    public const KINGDOMS_VOTE_MENU_FIELD_SANCTION_DURATION = 'kingdoms.vote.menu.fields.sanctionDuration';

// Stats / progress / leader
    public const KINGDOMS_VOTE_MENU_COUNTS = 'kingdoms.vote.menu.counts';     // {FOR} {AGAINST} {TOTAL}
    public const KINGDOMS_VOTE_MENU_PROGRESS = 'kingdoms.vote.menu.progress';   // {BAR}
    public const KINGDOMS_VOTE_MENU_LEADING_FOR = 'kingdoms.vote.menu.leading.for';
    public const KINGDOMS_VOTE_MENU_LEADING_AGAINST = 'kingdoms.vote.menu.leading.against';
    public const KINGDOMS_VOTE_MENU_LEADING_TIED = 'kingdoms.vote.menu.leading.tied';

    public const KINGDOMS_VOTE_MENU_YOU_VOTED_FOR = 'kingdoms.vote.menu.youVoted.for';
    public const KINGDOMS_VOTE_MENU_YOU_VOTED_AGAINST = 'kingdoms.vote.menu.youVoted.against';

    public const KINGDOMS_VOTE_MENU_ACTION_FOR = 'kingdoms.vote.menu.actions.for';
    public const KINGDOMS_VOTE_MENU_ACTION_AGAINST = 'kingdoms.vote.menu.actions.against';
    public const KINGDOMS_VOTE_MENU_ACTION_FOR_KEEP = 'kingdoms.vote.menu.actions.for.keep';
    public const KINGDOMS_VOTE_MENU_ACTION_FOR_SWITCH = 'kingdoms.vote.menu.actions.for.switch';
    public const KINGDOMS_VOTE_MENU_ACTION_AGAINST_KEEP = 'kingdoms.vote.menu.actions.against.keep';
    public const KINGDOMS_VOTE_MENU_ACTION_AGAINST_SWITCH = 'kingdoms.vote.menu.actions.against.switch';
    public const KINGDOMS_VOTE_MENU_ACTION_VIEW_STATS = 'kingdoms.vote.menu.actions.against.stats';

    //res
    public const KINGDOM_VOTE_INCORRECT_TYPE = 'kingdoms.vote.errors.incorrectType';

    // High-level / feedback
// Listing menu
    public const KINGDOMS_VOTE_LIST_MENU_HEADER = 'kingdoms.vote.list.menu.header';
    public const KINGDOMS_VOTE_LIST_MENU_TEXT = 'kingdoms.vote.list.menu.text';
    public const KINGDOMS_VOTE_LIST_MENU_ROW = 'kingdoms.vote.list.menu.row';

    //Vote handlers
    public const KINGDOMS_VOTE_HANDLER_KICK_PASSED = 'kingdoms.vote.handlers.kick.passed';

    public const KINGDOMS_UPGRADE_USAGE = 'kingdoms.upgrade.usage';
    public const KINGDOMS_UPGRADE_SUCCESS = 'kingdoms.upgrade.success';
    public const KINGDOMS_UPGRADE_INSUFFICIENT_FUNDS = 'kingdoms.upgrade.insufficientFunds';
    public const KINGDOMS_UPGRADE_ALREADY_OWNED = 'kingdoms.upgrade.alreadyOwned';
    public const KINGDOMS_UPGRADE_INVALID_PERK = 'kingdoms.upgrade.invalidPerk';

    public const KINGDOMS_SHIELD_USAGE = 'kingdoms.shield.usage';
    public const KINGDOMS_SHIELD_SUCCESS = 'kingdoms.shield.success';
    public const KINGDOMS_SHIELD_ACTIVE = 'kingdoms.shield.active';
    public const KINGDOMS_SHIELD_INSUFFICIENT_FUNDS = 'kingdoms.shield.insufficientFunds';

    public const KINGDOMS_ALARM_SUCCESS = 'kingdoms.alarm.success';
    public const KINGDOMS_ALARM_BROADCAST = 'kingdoms.alarm.broadcast';
    public const KINGDOMS_ALARM_COOLDOWN = 'kingdoms.alarm.cooldown';
    public const KINGDOMS_ALARM_FLOATING_TEXT = 'kingdoms.alarm.floating-text';

    public const KINGDOMS_RALLY_SUCCESS = 'kingdoms.rally.success';
    public const KINGDOMS_RALLY_TELEPORT = 'kingdoms.rally.teleport';
    public const KINGDOMS_RALLY_NO_RALLY = 'kingdoms.rally.noRally';
    public const KINGDOMS_RALLY_COOLDOWN = 'kingdoms.rally.cooldown';

    public const KINGDOMS_BOUNTY_USAGE = 'kingdoms.bounty.usage';
    public const KINGDOMS_BOUNTY_SUCCESS = 'kingdoms.bounty.success';
    public const KINGDOMS_BOUNTY_INSUFFICIENT_FUNDS = 'kingdoms.bounty.insufficientFunds';
    public const KINGDOMS_BOUNTY_SELF = 'kingdoms.bounty.self';
    public const KINGDOMS_BOUNTY_NOT_ENEMY = 'kingdoms.bounty.notEnemy';
    public const KINGDOMS_BOUNTY_ALREADY_EXISTS = 'kingdoms.bounty.alreadyExists';
    public const KINGDOMS_BOUNTY_ACTIVE_ENTRY = 'kingdoms.bounty.activeEntry';
    public const KINGDOMS_BOUNTY_INACTIVE_ENTRY = 'kingdoms.bounty.inactiveEntry';
    public const KINGDOMS_BOUNTY_NO_PAGE = 'kingdoms.bounty.noPage';
    public const KINGDOMS_BOUNTY_PAGE_HEADER = 'kingdoms.bounty.pageHeader';
    public const KINGDOMS_BOUNTY_CLAIMED = 'kingdoms.bounty.claimed';

    // Betrayal System
    public const KINGDOMS_BETRAYAL_COOLDOWN = 'kingdoms.betrayal.cooldown';
    public const KINGDOMS_BETRAYAL_CONFIRM = 'kingdoms.betrayal.confirm';
    public const KINGDOMS_BETRAYAL_SUCCESS = 'kingdoms.betrayal.success';
    public const KINGDOMS_BETRAYAL_BROADCAST = 'kingdoms.betrayal.broadcast';
    public const KINGDOMS_BETRAYAL_CONSEQUENCES = 'kingdoms.betrayal.consequences';
    public const KINGDOMS_BETRAYAL_NOT_BATTLE_PHASE = 'kingdoms.betrayal.notInBattle';
    public const KINGDOMS_BETRAYAL_SELF = 'kingdoms.betrayal.self';

    // Loyalty System
    public const KINGDOMS_LOYALTY_HIGH = 'kingdoms.loyalty.high';
    public const KINGDOMS_LOYALTY_MEDIUM = 'kingdoms.loyalty.medium';
    public const KINGDOMS_LOYALTY_LOW = 'kingdoms.loyalty.low';
    public const KINGDOMS_LOYALTY_BONUS = 'kingdoms.loyalty.bonus';
    public const KINGDOMS_VOTE_ALREADY_VOTED_KICK = 'kingdoms.kick.errors.alreadyKick';

    // Messages du système de vote
    public const KINGDOMS_VOTE_CREATED_NOTIFICATION = 'kingdoms.vote.created_notification';
    public const KINGDOMS_VOTE_MEMBER_VOTED_NOTIFICATION = 'kingdoms.vote.member_voted_notification';
    public const KINGDOMS_VOTE_EXPIRED_NOTIFICATION = 'kingdoms.vote.expired_notification';
    public const KINGDOMS_VOTE_PASSED_NOTIFICATION = 'kingdoms.vote.passed_notification';
    public const KINGDOMS_VOTE_FAILED_NOTIFICATION = 'kingdoms.vote.failed_notification';
    public const KINGDOMS_VOTE_REMINDER_NOTIFICATION = 'kingdoms.vote.reminder_notification';
    public const KINGDOMS_VOTE_QUORUM_NOT_MET = 'kingdoms.vote.quorum_not_met';
    public const KINGDOMS_VOTE_MAJORITY_NOT_MET = 'kingdoms.vote.majority_not_met';

    // Qorum Settings Messages & Menus
    const KINGDOMS_VOTE_QUORUM_MENU_TITLE = 'kingdoms.vote.quorum.menu.title';

    // Quorum menu sections
    public const KINGDOMS_VOTE_QUORUM_GENERAL_HEADER = 'kingdoms.vote.quorum.menu.sections.general.header';
    public const KINGDOMS_VOTE_QUORUM_GENERAL_TYPE = 'kingdoms.vote.quorum.menu.sections.general.type';
    public const KINGDOMS_VOTE_QUORUM_GENERAL_TARGET = 'kingdoms.vote.quorum.menu.sections.general.target';
    public const KINGDOMS_VOTE_QUORUM_GENERAL_REASON = 'kingdoms.vote.quorum.menu.sections.general.reason';
    public const KINGDOMS_VOTE_QUORUM_GENERAL_PROPOSED_BY = 'kingdoms.vote.quorum.menu.sections.general.proposedBy';

    public const KINGDOMS_VOTE_QUORUM_PARTICIPATION_HEADER = 'kingdoms.vote.quorum.menu.sections.participation.header';
    public const KINGDOMS_VOTE_QUORUM_PARTICIPATION_TOTAL = 'kingdoms.vote.quorum.menu.sections.participation.totalMembers';
    public const KINGDOMS_VOTE_QUORUM_PARTICIPATION_VOTES = 'kingdoms.vote.quorum.menu.sections.participation.votesCount';
    public const KINGDOMS_VOTE_QUORUM_PARTICIPATION_RATE = 'kingdoms.vote.quorum.menu.sections.participation.participation';

    public const KINGDOMS_VOTE_QUORUM_RESULTS_HEADER = 'kingdoms.vote.quorum.menu.sections.results.header';
    public const KINGDOMS_VOTE_QUORUM_RESULTS_FOR = 'kingdoms.vote.quorum.menu.sections.results.for';
    public const KINGDOMS_VOTE_QUORUM_RESULTS_AGAINST = 'kingdoms.vote.quorum.menu.sections.results.against';

    public const KINGDOMS_VOTE_QUORUM_REQUIREMENTS_HEADER = 'kingdoms.vote.quorum.menu.sections.requirements.header';
    public const KINGDOMS_VOTE_QUORUM_REQUIREMENTS_MIN_PARTICIPATION = 'kingdoms.vote.quorum.menu.sections.requirements.minParticipation';
    public const KINGDOMS_VOTE_QUORUM_REQUIREMENTS_MIN_MAJORITY = 'kingdoms.vote.quorum.menu.sections.requirements.minMajority';
    public const KINGDOMS_VOTE_QUORUM_REQUIREMENTS_MIN_VOTES = 'kingdoms.vote.quorum.menu.sections.requirements.minVotes';

    public const KINGDOMS_VOTE_QUORUM_CONDITIONS_HEADER = 'kingdoms.vote.quorum.menu.sections.conditions.header';
    public const KINGDOMS_VOTE_QUORUM_CONDITIONS_QUORUM_MET = 'kingdoms.vote.quorum.menu.sections.conditions.quorumMet';
    public const KINGDOMS_VOTE_QUORUM_CONDITIONS_MAJORITY_MET = 'kingdoms.vote.quorum.menu.sections.conditions.majorityMet';

    public const KINGDOMS_VOTE_QUORUM_FINAL_HEADER = 'kingdoms.vote.quorum.menu.sections.final.header';
    public const KINGDOMS_VOTE_QUORUM_FINAL_SUCCESS = 'kingdoms.vote.quorum.menu.sections.final.success';
    public const KINGDOMS_VOTE_QUORUM_FINAL_FAILURE = 'kingdoms.vote.quorum.menu.sections.final.failure';
    public const KINGDOMS_VOTE_QUORUM_FINAL_REASON_QUORUM = 'kingdoms.vote.quorum.menu.sections.final.reasons.quorum';
    public const KINGDOMS_VOTE_QUORUM_FINAL_REASON_MAJORITY = 'kingdoms.vote.quorum.menu.sections.final.reasons.majority';

    // Vote confirmation system
    public const KINGDOMS_VOTE_CONFIRM_HEADER = 'kingdoms.vote.confirm.header';
    public const KINGDOMS_VOTE_CONFIRM_CONTENT = 'kingdoms.vote.confirm.content';
    public const KINGDOMS_VOTE_CONFIRM_YES = 'kingdoms.vote.confirm.yes';
    public const KINGDOMS_VOTE_CONFIRM_NO = 'kingdoms.vote.confirm.no';
    public const KINGDOMS_VOTE_CANCELLED = 'kingdoms.vote.confirm.cancelled';
    public const KINGDOMS_VOTE_CREATED = 'kingdoms.vote.confirm.created';
    public const KINGDOMS_VOTE_ALREADY_EXISTS = 'kingdoms.vote.confirm.alreadyExists';
    public const KINGDOMS_BAN_CONFIRM_HEADER = 'kingdoms.ban.confirm.header';
    public const KINGDOMS_BAN_CONFIRM_CONTENT = 'kingdoms.ban.confirm.content';
    public const KINGDOMS_CANT_JOIN_EXCLUDED_WITH_DETAILS = 'kingdoms.onJoin.errors.excluded.withDetails';
    public const KINGDOMS_CANT_JOIN_EXCLUDED_NO_DETAILS = 'kingdoms.onJoin.errors.excluded.noDetails';
}
