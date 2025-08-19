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
    -- # :uuid string
    -- # :name string
    -- # :kingdom string
    -- # :abilities string
        INSERT INTO players (uuid, name, kingdom, abilities)
        VALUES (:uuid, :name, :kingdom, :abilities)
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
-- # }

-- # { kit_requirements
    -- # { init
        CREATE TABLE IF NOT EXISTS kit_requirement (
            id INTEGER NOT NULL, # c'est bon ça ?
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
-- # }

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
-- # }

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

-- # }

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
-- # }

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
-- # }