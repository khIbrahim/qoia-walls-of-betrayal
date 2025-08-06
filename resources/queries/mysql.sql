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