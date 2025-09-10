-- #! mysql
-- # { history
    -- # { init
        CREATE TABLE IF NOT EXISTS punishment_history (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            target       VARCHAR(255) NOT NULL,
            type         VARCHAR(100) NOT NULL,
            reason       TEXT,
            staff        VARCHAR(255),
            created_at   INT,
            expiration   BIGINT DEFAULT NULL
        );
    -- # }

    -- # { add
    -- # :target string
    -- # :type string
    -- # :reason string
    -- # :staff string
    -- # :created_at int
    -- # :expiration ?int
        INSERT INTO punishment_history(target, type, reason, staff, created_at, expiration)
        VALUES (:target, :type, :reason, :staff, :created_at, :expiration);
    -- # }

    -- # { get
    -- # :username string
    -- # :type string
        SELECT * FROM punishment_history WHERE target = :username AND type = :type ORDER BY created_at DESC;
    -- # }

-- # }
