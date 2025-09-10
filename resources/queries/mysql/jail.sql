-- #! mysql
-- # { jail
    -- # { init
        CREATE TABLE IF NOT EXISTS jails(
            id                INT AUTO_INCREMENT PRIMARY KEY,
            target            VARCHAR(255) NOT NULL,
            reason            TEXT,
            staff             VARCHAR(255),
            quest_progress    INT DEFAULT 0,
            quest_objective   INT DEFAULT 0,
            original_location TEXT DEFAULT NULL,
            created_at        INT,
            expiration        BIGINT DEFAULT NULL,
            active            INT DEFAULT 1
        );
    -- # }

    -- # { get
        SELECT * FROM jails WHERE active = 1;
    -- # }

    -- # { create
    -- # :target string
    -- # :reason string
    -- # :staff string
    -- # :quest_progress int
    -- # :quest_objective int
    -- # :original_location ?string
    -- # :expiration ?int
        INSERT INTO jails(target, reason, staff, quest_progress, quest_objective, original_location, created_at, expiration, active)
        VALUES (:target, :reason, :staff, :quest_progress, :quest_objective, :original_location, UNIX_TIMESTAMP(), :expiration, 1)
        ON DUPLICATE KEY UPDATE
            reason = VALUES(reason),
            staff = VALUES(staff),
            quest_progress = VALUES(quest_progress),
            quest_objective = VALUES(quest_objective),
            original_location = VALUES(original_location),
            expiration = VALUES(expiration),
            active = 1;
    -- # }

    -- # { delete
    -- # :target string
        DELETE FROM jails WHERE target = :target;
    -- # }

    -- # { update
    -- # :target string
    -- # :quest_progress int
    -- # :quest_objective int
        UPDATE jails
        SET quest_progress = :quest_progress,
            quest_objective = :quest_objective
        WHERE target = :target;
    -- # }
-- #}
