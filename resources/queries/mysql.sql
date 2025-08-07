-- #! mysql
-- # { players
    -- # { init
        CREATE TABLE IF NOT EXISTS players(
            uuid VARCHAR(36) NOT NULL,
            kingdom VARCHAR(64) DEFAULT NULL,

            PRIMARY KEY (uuid),
            INDEX idx_uuid(uuid)
        );
    -- # }

    -- # { load
    -- # :uuid string
        SELECT * FROM players WHERE uuid = :uuid;
    -- # }

    -- # { insert
    -- # :uuid string
        INSERT INTO players(uuid) VALUES (:uuid);
    -- # }

    -- # { setKingdom
    -- # :uuid string
    -- # :kingdom string
        INSERT INTO players (uuid, kingdom)
        VALUES (:uuid, :kingdom)
        ON DUPLICATE KEY UPDATE kingdom = VALUES(kingdom);
    -- # }
-- # }

-- # { kit_requirements
    -- # { init
        CREATE TABLE IF NOT EXISTS kit_requirement (
            kingdom_id VARCHAR(64) NOT NULL,
            kit_id VARCHAR(64) NOT NULL,
            amount INTEGER NOT NULL
        );
    -- # }
-- # }