-- #! mysql
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
