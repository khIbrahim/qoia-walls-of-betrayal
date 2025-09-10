-- #! mysql
-- # { mute

    -- # { init
        CREATE TABLE IF NOT EXISTS mute (
            id          INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            target      VARCHAR(255) NOT NULL,
            expiration  BIGINT NULL,
            reason      TEXT,
            staff       VARCHAR(255),
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
            active      TINYINT(1) DEFAULT 1
        );
    -- # }

    -- # { get
        SELECT * FROM mute;
    -- # }

    -- # { create
    -- # :target string
    -- # :expiration ?int
    -- # :reason string
    -- # :staff string
        INSERT INTO mute(target, expiration, reason, staff)
        VALUES (:target, :expiration, :reason, :staff);
    -- # }

    -- # { delete
    -- # :username string
        DELETE FROM mute WHERE target = :username;
    -- # }

-- # }
