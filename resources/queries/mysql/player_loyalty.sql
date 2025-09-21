-- #! mysql
-- # { player_loyalty
    -- # { init
        CREATE TABLE IF NOT EXISTS player_loyalty
        (
            uuid                VARCHAR(36) PRIMARY KEY,
            username            VARCHAR(32) NOT NULL,
            kingdom_id          VARCHAR(50) NOT NULL,
            loyalty_score       INT       DEFAULT 50,
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

    -- # { getByName
    -- # :username string
        SELECT *
        FROM player_loyalty
        WHERE username = :username;
    -- # }

    -- # { insert
    -- # :uuid string
    -- # :username string
    -- # :kingdom_id string
        INSERT IGNORE INTO player_loyalty(uuid, username, kingdom_id)
        VALUES (:uuid, :username, :kingdom_id);
    -- # }

    -- # { update
    -- # :uuid string
    -- # :username string
    -- # :kingdom_id string
    -- # :loyalty_score int
    -- # :contributions_count int
    -- # :betrayals_count int
    -- # :last_betrayal ?int
        INSERT INTO player_loyalty(uuid, username, kingdom_id, loyalty_score, contributions_count, betrayals_count, last_betrayal)
        VALUES (:uuid, :username, :kingdom_id, :loyalty_score, :contributions_count, :betrayals_count, :last_betrayal)
        ON DUPLICATE KEY UPDATE
            username            = VALUES(username),
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
        INSERT INTO player_loyalty(uuid, username, kingdom_id, contributions_count, last_contribution, loyalty_score)
        VALUES (:uuid, :username, :kingdom_id, 1, CURRENT_TIMESTAMP, loyalty_score + 1)
        ON DUPLICATE KEY UPDATE contributions_count = contributions_count + 1,
            last_contribution   = CURRENT_TIMESTAMP;
    -- # }

    -- # { addScore
    -- # :uuid string
    -- # :score int
        UPDATE player_loyalty
        SET loyalty_score = loyalty_score + :score
        WHERE uuid = :uuid;
    -- # }

    -- # { updateScore
    -- # :uuid string
    -- # :score int
        UPDATE player_loyalty
        SET loyalty_score = :score
        WHERE uuid = :uuid;
    -- # }
-- # }
