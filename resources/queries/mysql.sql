-- #! mysql
-- # { players
    -- # { init
        CREATE TABLE IF NOT EXISTS players(
            uuid VARCHAR(36) NOT NULL,
            name VARCHAR(32) NOT NULL,
            kingdom VARCHAR(64) DEFAULT NULL,
            abilities JSON,

            PRIMARY KEY (uuid),
            INDEX idx_uuid(uuid)
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