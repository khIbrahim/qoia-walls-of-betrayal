-- #! mysql
-- # { kingdoms
    -- # { init
        CREATE TABLE IF NOT EXISTS kingdoms(
            id VARCHAR(50) NOT NULL PRIMARY KEY,
            xp BIGINT NOT NULL DEFAULT 0,
            balance BIGINT NOT NULL DEFAULT 0,
            kills INT NOT NULL DEFAULT 0,
            deaths INT NOT NULL DEFAULT 0,
            spawn JSON,
            borders JSON,
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

    -- # { updateBorders
    -- # :id string
    -- # :borders string
        UPDATE kingdoms
        SET borders = :borders
        WHERE id = :id;
    -- # }
-- #}
