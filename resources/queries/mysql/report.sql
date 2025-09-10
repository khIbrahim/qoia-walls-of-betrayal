-- #! mysql
-- # { report
    -- # { init
    CREATE TABLE IF NOT EXISTS reports (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        target      VARCHAR(255) NOT NULL,
        reason      TEXT,
        staff       VARCHAR(255),
        created_at  INT,
        active      INT DEFAULT 1,
        expiration  BIGINT DEFAULT (UNIX_TIMESTAMP() + 604800)
    );
    -- # }

    -- # { get
        SELECT * FROM reports;
    -- # }

    -- # { create
    -- # :target string
    -- # :reason string
    -- # :staff string
    -- # :expiration ?int
        INSERT INTO reports(target, reason, staff, created_at, active, expiration)
        VALUES(:target, :reason, :staff, UNIX_TIMESTAMP(), 1, :expiration);
    -- # }

    -- # { delete
    -- # :id int
        DELETE FROM reports WHERE id = :id;
    -- # }

    -- # { archive
    -- # :id int
        UPDATE reports
        SET
            active = 0
        WHERE id = :id;
    -- # }

-- # }
