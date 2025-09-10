-- #! mysql
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