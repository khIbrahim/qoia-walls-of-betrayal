-- #! mysql
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
