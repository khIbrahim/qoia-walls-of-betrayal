-- #! mysql
-- # { ban
    -- # { init
        CREATE TABLE IF NOT EXISTS bans (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            target         VARCHAR(255) NOT NULL,
            reason         TEXT,
            staff          VARCHAR(255),
            created_at     INT,
            expiration     BIGINT DEFAULT NULL,
            silent         INT DEFAULT 0,
            active         INT DEFAULT 1
        );
    -- # }

    -- # { add
        -- # :target string
        -- # :reason string
        -- # :staff string
        -- # :created_at int
        -- # :expiration ?int
        -- # :silent int
        INSERT INTO bans(target, reason, staff, created_at, expiration, silent)
        VALUES (:target, :reason, :staff, :created_at, :expiration, :silent)
        ON DUPLICATE KEY UPDATE
            reason = VALUES(reason),
            staff = VALUES(staff),
            created_at = VALUES(created_at),
            expiration = VALUES(expiration),
            silent = VALUES(silent),
            active = 1;
    -- # }

    -- # { remove
    -- # :username string
        DELETE FROM bans WHERE target = :username;
    -- # }

    -- # { getAll
        SELECT * FROM bans WHERE active = 1;
    -- # }

    -- # { get
    -- # :target string
        SELECT * FROM bans WHERE target = :target AND expiration > :time;
    -- # }

-- # }
