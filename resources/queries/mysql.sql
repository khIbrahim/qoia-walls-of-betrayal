-- #! mysql
-- # { players
    -- # { init
        CREATE TABLE IF NOT EXISTS players(
            uuid VARCHAR(36) NOT NULL,
            name VARCHAR(32) NOT NULL,
            kingdom VARCHAR(64) DEFAULT NULL,
            abilities JSON,
            kills INT DEFAULT 0,
            deaths INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (uuid),
            INDEX idx_uuid(uuid),
            INDEX idx_username(name)
        );
    -- # }

    -- # { updateAbilities
    -- # :abilities string
    -- # :uuid string
        UPDATE players SET abilities = :abilities WHERE uuid = :uuid;
    -- # }

    -- # { load
    -- # :uuid string
        SELECT * FROM players WHERE uuid = :uuid;
    -- # }

    -- # { loadByName
    -- # :username string
        SELECT * FROM players WHERE name = :username;
    -- # }

    -- # { insert
    -- # :uuid string
    -- # :name string
        INSERT INTO players(uuid, name) VALUES (:uuid, :name);
    -- # }

    -- # { setKingdom
    -- # :uuid ?string
    -- # :username ?string
    -- # :kingdom ?string
    -- # :abilities string
        INSERT INTO players (uuid, name, kingdom, abilities)
        VALUES (:uuid, :username, :kingdom, :abilities)
        ON DUPLICATE KEY UPDATE
            kingdom = VALUES(kingdom),
            abilities = VALUES(abilities);
    -- # }

    -- # { incrementKills
    -- # :uuid string
        UPDATE players SET kills = kills + 1 WHERE uuid = :uuid;
    -- # }

    -- # { incrementDeaths
    -- # :uuid string
        UPDATE players SET deaths = deaths + 1 WHERE uuid = :uuid;
    -- # }

    -- # { getKingdomPlayersCount
    -- # :id string
SELECT COUNT(*) AS total
FROM players
WHERE kingdom = :id;
-- # }

-- # }

-- # { kit_requirements
    -- # { init
        CREATE TABLE IF NOT EXISTS kit_requirement (
            id INTEGER NOT NULL,
            kingdom_id VARCHAR(64) NOT NULL,
            kit_id VARCHAR(64) NOT NULL,
            amount INTEGER DEFAULT 0 NOT NULL,
            PRIMARY KEY (id, kingdom_id, kit_id)
        );
    -- # }

    -- # { insert
    -- # :id int
    -- # :kingdom string
    -- # :kit string
        INSERT INTO kit_requirement(id, kingdom_id, kit_id) VALUES (:id, :kingdom, :kit);
    -- # }

    -- # { getByKingdomAndKit
    -- # :kingdom string
    -- # :kit string
        SELECT * FROM kit_requirement WHERE kingdom_id = :kingdom AND kit_id = :kit;
    -- # }

    -- # { increment
    -- # :id int
    -- # :kingdom string
    -- # :kit string
    -- # :progress int
    -- # :max int
        UPDATE kit_requirement
        SET
            amount = MIN(amount + :progress, :max)
        WHERE kingdom_id = :kingdom AND kit_id = :kit AND id = :id;
    -- # }
-- #}

-- # { cooldowns
    -- # { init
        CREATE TABLE IF NOT EXISTS cooldowns(
            id BIGINT NOT NULL AUTO_INCREMENT,
            identifier VARCHAR(64) NOT NULL,
            cooldown_type VARCHAR(64) NOT NULL,
            expiry_time BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            UNIQUE KEY uk_identifier_type (identifier, cooldown_type),
            INDEX idx_identifier (identifier),
            INDEX idx_cooldown_type (cooldown_type),
            INDEX idx_expiry_time (expiry_time)
        );
    -- # }

    -- # { getActive
    -- # :currentTime int
        SELECT identifier, cooldown_type, expiry_time FROM cooldowns
        WHERE expiry_time > :currentTime;
    -- # }

    -- # { getPlayerCooldowns
    -- # :id string
    -- # :currentTime int
        SELECT cooldown_type, expiry_time
        FROM cooldowns
        WHERE
            identifier = :id AND
            expiry_time > :currentTime;
    -- # }

    -- # { upsert
    -- # :id string
    -- # :type string
    -- # :expiry int
        INSERT INTO cooldowns(identifier, cooldown_type, expiry_time)
        VALUES (:id, :type, :expiry)
        ON DUPLICATE KEY UPDATE
            expiry_time = :expiry;
    -- # }

    -- # { remove
    -- # :id string
    -- # :type string
        DELETE FROM cooldowns
        WHERE identifier = :id AND cooldown_type = :type;
    -- # }

    -- # { cleanupExpired
    -- # :currentTime int
        DELETE FROM cooldowns
        WHERE expiry_time <= :currentTime;
    -- # }

    -- # { getPlayerSpecificCooldown
    -- # :id string
    -- # :type string
    -- # :currentTime int
        SELECT expiry_time FROM cooldowns
        WHERE
            identifier = :id AND
            cooldown_type = :type AND
            expiry_time > :currentTime;
    -- # }
-- #}

-- # { economy
    -- # { init
        CREATE TABLE IF NOT EXISTS economy(
            uuid VARCHAR(36) PRIMARY KEY,
            username VARCHAR(32) NOT NULL,
            amount DECIMAL(64, 2) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_uuid(uuid),
            INDEX idx_username(username)
        );
    -- # }

    -- # { get
    -- # :uuid ?string
    -- # :name ?string
        SELECT
            e.*,
            DENSE_RANK() OVER (ORDER BY e.amount DESC) AS position
        FROM economy e
        WHERE e.uuid = :uuid OR e.username = :name;
    -- # }

    -- # { insert
    -- # :uuid string
    -- # :name string
        INSERT IGNORE INTO economy(uuid, username) VALUES (:uuid, :name);
    -- # }

    -- # { add
    -- # :uuid ?string
    -- # :name ?string
    -- # :amount int
        UPDATE economy
        SET amount = amount + :amount
        WHERE (uuid = :uuid OR username = :name);
    -- # }

    -- # { subtract
    -- # :uuid ?string
    -- # :name ?string
    -- # :amount int
        UPDATE economy
        SET amount = amount - :amount
        WHERE (uuid = :uuid OR username = :name) AND amount >= :amount;
    -- # }

    -- # { transfer

        -- # { begin
            BEGIN;
        -- # }

        -- # { debitSender
        -- # :s_uuid string
        -- # :s_name ?string
        -- # :amount int
            UPDATE economy
            SET amount = amount - :amount
            WHERE (uuid = :s_uuid OR username = :s_name)
              AND amount >= :amount;
        -- # }

        -- # { creditReceiver
        -- # :r_uuid string
        -- # :r_name ?string
        -- # :amount int
            UPDATE economy
            SET amount = amount + :amount
            WHERE (uuid = :r_uuid OR username = :r_name);
        -- # }

        -- # { commit
            COMMIT;
        -- # }

        -- # { rollback
            ROLLBACK;
        -- # }

    -- # }

    -- # { top
    -- # :limit int
    -- # :offset int
        SELECT
            e.username,
            e.uuid,
            e.amount,
            DENSE_RANK() OVER (ORDER BY e.amount DESC) AS position
        FROM economy e
        ORDER BY e.amount DESC, e.username
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { set
    -- # :name string # pour le moment ça va marcher seulement avec le name
    -- # :amount int
        UPDATE economy
        SET amount = :amount
        WHERE username = :name;
    -- # }

-- #}

-- # { roles
    -- # { init
        CREATE TABLE IF NOT EXISTS player_roles(
            uuid VARCHAR(36) NOT NULL PRIMARY KEY,
            username VARCHAR(64),
            role_id VARCHAR(64) NOT NULL,
            subRoles JSON,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at BIGINT DEFAULT NULL,
            permissions JSON,
            INDEX idx_role_id(role_id),
            INDEX idx_expires_at(expires_at),
            INDEX idx_username(username),
            INDEX idx_uuid(uuid)
        );
    -- # }

    -- # { assign
    -- # :uuid string
    -- # :username ?string
    -- # :role_id string
    -- # :subRoles ?string
    -- # :permissions ?string
    -- # :expires_at ?int
        INSERT INTO player_roles(uuid, role_id, expires_at, username, subRoles, permissions)
        VALUES (:uuid, :role_id, :expires_at, :username, :subRoles, :permissions)
        ON DUPLICATE KEY UPDATE
            role_id = VALUES(role_id),
            assigned_at = CURRENT_TIMESTAMP,
            expires_at = VALUES(expires_at);
    -- # }

    -- # { get
    -- # :uuid ?string
    -- # :username ?string
        SELECT * FROM player_roles WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { remove
    -- # :uuid string
        DELETE FROM player_roles WHERE uuid = :uuid;
    -- # }

    -- # { updateRole
    -- # :uuid ?string
    -- # :username ?string
    -- # :role_id string
    -- # :expires_at ?int
        UPDATE player_roles
        SET role_id = :role_id,
            expires_at = :expires_at,
            assigned_at = CURRENT_TIMESTAMP
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { getPermissions
    -- # :uuid ?string
    -- # :username ?string
        SELECT permissions FROM player_roles
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { updatePermissions
    -- # :uuid ?string
    -- # :username ?string
    -- # :permissions string
        UPDATE player_roles
        SET permissions = :permissions
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { getSubRoles
    -- # :uuid ?string
    -- # :username ?string
        SELECT subRoles FROM player_roles
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { updateSubRoles
    -- # :uuid ?string
    -- # :username ?string
    -- # :subRoles string
        UPDATE player_roles
        SET subRoles = :subRoles
        WHERE (uuid = :uuid OR username = :username);
    -- # }
-- #}

-- # { vaults
    -- # { init
        CREATE TABLE IF NOT EXISTS vaults(
            uuid VARCHAR(36) NOT NULL,
            name VARCHAR(64) NOT NULL,
            number TINYINT UNSIGNED NOT NULL,
            items BLOB NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (uuid, name, number),
            INDEX idx_uuid(uuid),
            INDEX idx_name(name)
        );
    -- # }

    -- # { open
    -- # :uuid ?string
    -- # :username ?string
    -- # :number int
        SELECT items FROM vaults
        WHERE (uuid = :uuid OR name = :username) AND number = :number;
    -- # }

    -- # { close
    -- # :uuid ?string
    -- # :username ?string
    -- # :number int
    -- # :items string
        INSERT INTO vaults(uuid, name, number, items)
        VALUES (:uuid, :username, :number, :items)
        ON DUPLICATE KEY UPDATE
            items = VALUES(items);
    -- # }
-- # }

-- # { kingdoms
    -- # { init
        CREATE TABLE IF NOT EXISTS kingdoms(
            id VARCHAR(50) NOT NULL PRIMARY KEY,
            xp BIGINT NOT NULL DEFAULT 0,
            balance BIGINT NOT NULL DEFAULT 0,
            kills INT NOT NULL DEFAULT 0,
            deaths INT NOT NULL DEFAULT 0,
            spawn JSON,
            rally_point    JSON,
            shield_active  TINYINT(1) DEFAULT 0,
            shield_expires BIGINT     DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
    -- # }

    -- # { get
    -- # :id string
        SELECT * FROM kingdoms WHERE id = :id;
    -- # }

    -- # { insert
    -- # :id string
        INSERT INTO kingdoms(id) VALUES (:id);
    -- # }

    -- # { incrementKills
    -- # :id string
        UPDATE kingdoms SET kills = kills + 1 WHERE id = :id;
    -- # }

    -- # { incrementDeaths
    -- # :id string
        UPDATE kingdoms SET deaths = deaths + 1 WHERE id = :id;
    -- # }

    -- # { updateSpawn
    -- # :id string
    -- # :spawn string
        UPDATE kingdoms
        SET spawn = :spawn
        WHERE id = :id;
    -- # }

    -- # { updateRallyPoint
    -- # :id string
    -- # :rally_point string
UPDATE kingdoms
SET rally_point = :rally_point
WHERE id = :id;
-- # }

-- # { updateShield
-- # :id string
-- # :shield_active int
-- # :shield_expires int
UPDATE kingdoms
SET shield_active  = :shield_active,
    shield_expires = :shield_expires
WHERE id = :id;
-- # }

-- # { addXP
-- # :id string
-- # :amount int
UPDATE kingdoms
SET xp = xp + :amount
WHERE id = :id;
-- # }

-- # { addBalance
-- # :id string
-- # :amount int
UPDATE kingdoms
SET balance = balance + :amount
WHERE id = :id;
-- # }

-- # { subtractBalance
-- # :id string
-- # :amount int
UPDATE kingdoms
SET balance = balance - :amount
WHERE id = :id
  AND balance >= :amount;
-- # }
-- #}

-- # { kingdom_bans
-- # { init
CREATE TABLE IF NOT EXISTS kingdom_bans
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kingdom_id  VARCHAR(50) NOT NULL,
    target_uuid VARCHAR(36) NOT NULL,
    target_name VARCHAR(32) NOT NULL,
    reason      TEXT,
    staff       VARCHAR(32),
    created_at  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    expires_at  BIGINT     DEFAULT NULL,
    active      TINYINT(1) DEFAULT 1,
    INDEX idx_kingdom_id (kingdom_id),
    INDEX idx_target_uuid (target_uuid),
    INDEX idx_active (active)
);
-- # }

-- # { create
-- # :kingdom_id string
-- # :uuid string
-- # :name string
-- # :reason string
-- # :staff string
-- # :expires int
INSERT INTO kingdom_bans(kingdom_id, target_uuid, target_name, reason, staff, expires_at, active)
VALUES (:kingdom_id, :uuid, :name, :reason, :staff, :expires, 1);
-- # }

-- # { deactivate
-- # :kingdom_id string
-- # :uuid string
UPDATE kingdom_bans
SET active = 0
WHERE kingdom_id = :kingdom_id
  AND target_uuid = :uuid
  AND active = 1;
-- # }

-- # { isBanned
-- # :kingdom_id string
-- # :uuid string
-- # :time int
SELECT expires_at
FROM kingdom_bans
WHERE kingdom_id = :kingdom_id
  AND target_uuid = :uuid
  AND active = 1
  AND (expires_at IS NULL OR expires_at > :time)
ORDER BY expires_at DESC
LIMIT 1;
-- # }

-- # { getActive
-- # :time int
SELECT *
FROM kingdom_bans
WHERE active = 1
  AND (expires_at IS NULL OR expires_at > :time);
-- # }

-- # }

-- # { mute

    -- # { init
        CREATE TABLE IF NOT EXISTS mute (
            id          INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            target      VARCHAR(255) NOT NULL,
            expiration  BIGINT NULL,
            reason      TEXT,
            staff       VARCHAR(255),
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
            active      TINYINT(1) DEFAULT 1
        );
    -- # }

    -- # { get
        SELECT * FROM mute;
    -- # }

    -- # { create
    -- # :target string
    -- # :expiration ?int
    -- # :reason string
    -- # :staff string
        INSERT INTO mute(target, expiration, reason, staff)
        VALUES (:target, :expiration, :reason, :staff);
    -- # }

    -- # { delete
    -- # :username string
        DELETE FROM mute WHERE target = :username;
    -- # }

-- # }

-- # { history
    -- # { init
        CREATE TABLE IF NOT EXISTS punishment_history (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            target       VARCHAR(255) NOT NULL,
            type         VARCHAR(100) NOT NULL,
            reason       TEXT,
            staff        VARCHAR(255),
            created_at   INT,
            expiration   BIGINT DEFAULT NULL
        );
    -- # }

    -- # { add
    -- # :target string
    -- # :type string
    -- # :reason string
    -- # :staff string
    -- # :created_at int
    -- # :expiration ?int
        INSERT INTO punishment_history(target, type, reason, staff, created_at, expiration)
        VALUES (:target, :type, :reason, :staff, :created_at, :expiration);
    -- # }

    -- # { get
    -- # :username string
    -- # :type string
        SELECT * FROM punishment_history WHERE target = :username AND type = :type ORDER BY created_at DESC;
    -- # }

-- # }

-- # { ban
    -- # { init
        CREATE TABLE IF NOT EXISTS bans (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            target         VARCHAR(255) NOT NULL,
            reason         TEXT,
            staff          VARCHAR(255),
            created_at     INT,
            expiration     BIGINT DEFAULT NULL,
            silent         INT DEFAULT 0,
            active         INT DEFAULT 1
        );
    -- # }

    -- # { add
        -- # :target string
        -- # :reason string
        -- # :staff string
        -- # :created_at int
        -- # :expiration ?int
        -- # :silent int
        INSERT INTO bans(target, reason, staff, created_at, expiration, silent)
        VALUES (:target, :reason, :staff, :created_at, :expiration, :silent)
        ON DUPLICATE KEY UPDATE
            reason = VALUES(reason),
            staff = VALUES(staff),
            created_at = VALUES(created_at),
            expiration = VALUES(expiration),
            silent = VALUES(silent),
            active = 1;
    -- # }

    -- # { remove
    -- # :username string
        DELETE FROM bans WHERE target = :username;
    -- # }

    -- # { getAll
        SELECT * FROM bans WHERE active = 1;
    -- # }

    -- # { get
    -- # :target string
        SELECT * FROM bans WHERE target = :target AND expiration > :time;
    -- # }

-- # }

-- # { jail
    -- # { init
        CREATE TABLE IF NOT EXISTS jails(
            id                INT AUTO_INCREMENT PRIMARY KEY,
            target            VARCHAR(255) NOT NULL,
            reason            TEXT,
            staff             VARCHAR(255),
            quest_progress    INT DEFAULT 0,
            quest_objective   INT DEFAULT 0,
            original_location TEXT DEFAULT NULL,
            created_at        INT,
            expiration        BIGINT DEFAULT NULL,
            active            INT DEFAULT 1
        );
    -- # }

    -- # { get
        SELECT * FROM jails WHERE active = 1;
    -- # }

    -- # { create
    -- # :target string
    -- # :reason string
    -- # :staff string
    -- # :quest_progress int
    -- # :quest_objective int
    -- # :original_location ?string
    -- # :expiration ?int
        INSERT INTO jails(target, reason, staff, quest_progress, quest_objective, original_location, created_at, expiration, active)
        VALUES (:target, :reason, :staff, :quest_progress, :quest_objective, :original_location, UNIX_TIMESTAMP(), :expiration, 1)
        ON DUPLICATE KEY UPDATE
            reason = VALUES(reason),
            staff = VALUES(staff),
            quest_progress = VALUES(quest_progress),
            quest_objective = VALUES(quest_objective),
            original_location = VALUES(original_location),
            expiration = VALUES(expiration),
            active = 1;
    -- # }

    -- # { delete
    -- # :target string
        DELETE FROM jails WHERE target = :target;
    -- # }

    -- # { update
    -- # :target string
    -- # :quest_progress int
    -- # :quest_objective int
        UPDATE jails
        SET quest_progress = :quest_progress,
            quest_objective = :quest_objective
        WHERE target = :target;
    -- # }
-- #}

-- # { report
    -- # { init
    CREATE TABLE IF NOT EXISTS reports (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        target      VARCHAR(255) NOT NULL,
        reason      TEXT,
        staff       VARCHAR(255),
        created_at  INT,
        active      INT DEFAULT 1,
        expiration  BIGINT DEFAULT (UNIX_TIMESTAMP() + 604800)
    );
    -- # }

    -- # { get
        SELECT * FROM reports;
    -- # }

    -- # { create
    -- # :target string
    -- # :reason string
    -- # :staff string
    -- # :expiration ?int
        INSERT INTO reports(target, reason, staff, created_at, active, expiration)
        VALUES(:target, :reason, :staff, UNIX_TIMESTAMP(), 1, :expiration);
    -- # }

    -- # { delete
    -- # :id int
        DELETE FROM reports WHERE id = :id;
    -- # }

    -- # { archive
    -- # :id int
        UPDATE reports
        SET
            active = 0
        WHERE id = :id;
    -- # }

-- # }

-- # { floatingText
    -- # { init
        CREATE TABLE IF NOT EXISTS floating_text(
            id VARCHAR(32) PRIMARY KEY,
            text LONGTEXT,
            pos JSON
        );
    -- # }

    -- # { load
        SELECT * from floating_text;
    -- # }

    -- # { create
    -- # :id string
    -- # :text string
    -- # :pos string
        INSERT INTO floating_text(id, text, pos)
        VALUES (:id, :text, :pos);
    -- # }

    -- # { remove
    -- # :id string
        DELETE FROM floating_text
        WHERE id = :id;
    -- # }

    -- # { updateText
    -- # :id string
    -- # :text string
        UPDATE floating_text
        SET text = :text
        WHERE id = :id;
    -- # }
-- # }

-- # { npc
    -- # { init
        CREATE TABLE IF NOT EXISTS npc(
            id VARCHAR(36) PRIMARY KEY NOT NULL,
            name LONGTEXT,
            command VARCHAR(255),
            cooldown INTEGER DEFAULT 1,
            pos JSON,
            yaw FLOAT,
            pitch FLOAT,
            skin BLOB,
            skin_id VARCHAR(255),
            cape BLOB,
            geometry_name VARCHAR(255),
            geometry BLOB
        );
    -- # }

    -- # { create
    -- # :id string
    -- # :name string
    -- # :command string
    -- # :cooldown string
    -- # :pos string
    -- # :yaw float
    -- # :pitch float
    -- # :skin string
    -- # :skin_id string
    -- # :cape string
    -- # :geometry_name string
    -- # :geometry string
        INSERT INTO npc(id, name, command, cooldown, pos, yaw, pitch, skin, skin_id, cape, geometry_name, geometry)
        VALUES (:id, :name, :command, :cooldown, :pos, :yaw, :pitch, :skin, :skin_id, :cape, :geometry_name, :geometry);
    -- # }

    -- # { update
    -- # :id string
    -- # :name string
    -- # :command string
    -- # :cooldown string
    -- # :pos string
    -- # :yaw float
    -- # :pitch float
    -- # :skin string
    -- # :skin_id string
    -- # :cape string
    -- # :geometry_name string
    -- # :geometry string
        UPDATE npc
        SET name          = :name,
            command       = :command,
            cooldown      = :cooldown,
            pos           = :pos,
            yaw           = :yaw,
            pitch         = :pitch,
            skin          = :skin,
            skin_id       = :skin_id,
            cape          = :cape,
            geometry_name = :geometry_name,
            geometry      = :geometry
        WHERE id = :id;
    -- # }

    -- # { delete
    -- # :id string
        DELETE FROM npc WHERE id = :id;
    -- # }

    -- # { loadAll
        SELECT * FROM npc;
    -- # }
-- # }

-- # { kingdom_bounties
    -- # { init
CREATE TABLE IF NOT EXISTS kingdom_bounties
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kingdom_id    VARCHAR(50) NOT NULL,
    target_player VARCHAR(32) NOT NULL,
    amount        INT         NOT NULL,
    placed_by     VARCHAR(32) NOT NULL,
    created_at    TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    active        TINYINT(1) DEFAULT 1,
    taken_by      VARCHAR(32) NULL,
    strict        TINYINT(1) DEFAULT 0,
    INDEX idx_kingdom_id (kingdom_id),
    INDEX idx_target_player (target_player),
    INDEX idx_active (active)
);
-- # }

-- # { create
-- # :kingdom_id string
-- # :target_player string
-- # :amount int
-- # :placed_by string
-- # :strict int
INSERT INTO kingdom_bounties(kingdom_id, target_player, amount, placed_by, strict)
VALUES (:kingdom_id, :target_player, :amount, :placed_by, :strict);
-- # }

-- # { getAllActive
SELECT *
FROM kingdom_bounties
WHERE active = 1
ORDER BY amount DESC;
-- # }

-- # { getActive
-- # :target_player string
SELECT *
FROM kingdom_bounties
WHERE target_player = :target_player
  AND active = 1
ORDER BY amount DESC;
-- # }

-- # { deactivate
-- # :id int
-- # :takenBy ?string
UPDATE kingdom_bounties
SET active   = 0,
    taken_by = :takenBy
WHERE id = :id;
-- # }
-- # }

-- # { player_loyalty
-- # { init
CREATE TABLE IF NOT EXISTS player_loyalty
(
    uuid                VARCHAR(36) PRIMARY KEY,
    username            VARCHAR(32) NOT NULL,
    kingdom_id          VARCHAR(50) NOT NULL,
    loyalty_score       INT       DEFAULT 50,
    join_date           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_contribution   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    contributions_count INT       DEFAULT 0,
    betrayals_count     INT       DEFAULT 0,
    last_betrayal       TIMESTAMP   NULL,
    INDEX idx_kingdom_id (kingdom_id),
    INDEX idx_username (username)
);
-- # }

-- # { get
-- # :uuid string
SELECT *
FROM player_loyalty
WHERE uuid = :uuid;
-- # }

-- # { update
-- # :uuid string
-- # :username string
-- # :kingdom_id string
-- # :loyalty_score int
-- # :contributions_count int
-- # :betrayals_count int
-- # :last_betrayal int
INSERT INTO player_loyalty(uuid, username, kingdom_id, loyalty_score, contributions_count, betrayals_count,
                           last_betrayal)
VALUES (:uuid, :username, :kingdom_id, :loyalty_score, :contributions_count, :betrayals_count, :last_betrayal)
ON DUPLICATE KEY UPDATE username            = VALUES(username),
                        kingdom_id          = VALUES(kingdom_id),
                        loyalty_score       = VALUES(loyalty_score),
                        contributions_count = VALUES(contributions_count),
                        betrayals_count     = VALUES(betrayals_count),
                        last_betrayal       = VALUES(last_betrayal);
-- # }

-- # { addContribution
-- # :uuid string
-- # :username string
-- # :kingdom_id string
INSERT INTO player_loyalty(uuid, username, kingdom_id, contributions_count)
VALUES (:uuid, :username, :kingdom_id, 1)
ON DUPLICATE KEY UPDATE contributions_count = contributions_count + 1,
                        last_contribution   = CURRENT_TIMESTAMP;
-- # }
-- # }

-- # { kingdom_votes
-- # { init
CREATE TABLE IF NOT EXISTS kingdom_votes
(
    id                INT AUTO_INCREMENT PRIMARY KEY,
    kingdom_id        VARCHAR(50)                               NOT NULL,
    vote_type         ENUM ('kick', 'ban', 'upgrade', 'shield') NOT NULL,
    target            VARCHAR(32)                               NOT NULL,
    proposed_by       VARCHAR(32)                               NOT NULL,
    reason            TEXT,
    votes_for         INT                                                DEFAULT 0,
    votes_against     INT                                                DEFAULT 0,
    created_at        TIMESTAMP                                          DEFAULT CURRENT_TIMESTAMP,
    expires_at        INT UNSIGNED                              NOT NULL,
    sanction_duration INT UNSIGNED                              NOT NULL DEFAULT 0,
    status            ENUM ('active', 'passed', 'failed', 'expired')     DEFAULT 'active',
    INDEX idx_kingdom_id (kingdom_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    UNIQUE (kingdom_id, target, vote_type)
);
-- # }

-- # { load
SELECT *
FROM kingdom_votes;
-- # }

-- # { get
-- # :id int
SELECT id,
       kingdom_id,
       vote_type,
       target,
       proposed_by,
       reason,
       votes_for,
       votes_against,
       created_at,
       expires_at,
       status,
       sanction_duration
FROM kingdom_votes
WHERE id = :id;
-- # }

-- # { create
-- # :kingdom_id string
-- # :vote_type string
-- # :target string
-- # :proposed_by string
-- # :reason string
-- # :sanction_duration int
-- # :expires_at int
INSERT INTO kingdom_votes(kingdom_id, vote_type, target, proposed_by, reason, sanction_duration, expires_at)
VALUES (:kingdom_id, :vote_type, :target, :proposed_by, :reason, :sanction_duration, :expires_at)
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
-- # }

-- # { getActive
-- # :kingdom_id string
SELECT *
FROM kingdom_votes
WHERE kingdom_id = :kingdom_id
  AND status = 'active'
  AND expires_at > UNIX_TIMESTAMP()
ORDER BY created_at DESC;
-- # }

-- # { vote
-- # :vote_id int
-- # :voter_uuid string
-- # :voter_name string
-- # :vote_for int
INSERT INTO kingdom_vote_votes(vote_id, voter_uuid, voter_name, vote_for)
VALUES (:vote_id, :voter_uuid, :voter_name, :vote_for)
ON DUPLICATE KEY UPDATE vote_for = VALUES(vote_for);
-- # }

-- # { updateStatus
-- # :id int
-- # :status string
UPDATE kingdom_votes
SET status = :status
WHERE id = :id;
-- # }

-- # { updateVotes
-- # :id int
-- # :votesFor int
-- # :votesAgainst int
UPDATE kingdom_votes
SET votes_for     = :votesFor,
    votes_against = :votesAgainst
WHERE id = :id;
-- # }

-- # { deleteExpired
DELETE
FROM kingdom_votes
WHERE expires_at < UNIX_TIMESTAMP();
-- # }
-- #}

-- # { kingdom_vote_votes
-- # { init
CREATE TABLE IF NOT EXISTS kingdom_vote_votes
(
    vote_id    INT         NOT NULL,
    voter_uuid VARCHAR(36) NOT NULL,
    voter_name VARCHAR(36) NOT NULL,
    vote_for   TINYINT(1)  NOT NULL,
    voted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (vote_id, voter_uuid),
    INDEX idx_vote_id (vote_id),
    INDEX idx_voter_uuid (voter_uuid),
    UNIQUE (voter_uuid, vote_id),
    UNIQUE (voter_name, vote_id)
);
-- # }

-- # { count
-- # :id int
SELECT SUM(CASE WHEN vote_for = 1 THEN 1 ELSE 0 END) AS votes_for,
       SUM(CASE WHEN vote_for = 0 THEN 1 ELSE 0 END) AS votes_against,
       COUNT(*)                                      AS total
FROM kingdom_vote_votes
WHERE vote_id = :id;
-- # }

-- # { getVoterChoice
-- # :id int
-- # :name string
SELECT vote_for
FROM kingdom_vote_votes
WHERE vote_id = :id
  AND voter_name = :name;
-- # }
-- # }
