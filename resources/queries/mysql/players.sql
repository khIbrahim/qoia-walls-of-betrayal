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

    -- # { updateStats
    -- # :uuid string
    -- # :kills int
    -- # :deaths int
        UPDATE players
        SET kills = :kills, deaths = :deaths
        WHERE uuid = :uuid;
    -- # }

-- # }
