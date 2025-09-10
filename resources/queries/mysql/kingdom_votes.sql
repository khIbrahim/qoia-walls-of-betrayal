-- #! mysql
-- # { kingdom_votes
    -- # { init
        CREATE TABLE IF NOT EXISTS kingdom_votes
        (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            kingdom_id        VARCHAR(50)                               NOT NULL,
            vote_type         ENUM ('kick', 'ban', 'upgrade', 'shield') NOT NULL,
            target            VARCHAR(32)                               NOT NULL,
            proposed_by       VARCHAR(32)                               NOT NULL,
            reason            TEXT,
            votes_for         INT                                                DEFAULT 0,
            votes_against     INT                                                DEFAULT 0,
            created_at        TIMESTAMP                                          DEFAULT CURRENT_TIMESTAMP,
            expires_at        INT UNSIGNED                              NOT NULL,
            sanction_duration INT UNSIGNED                              NOT NULL DEFAULT 0,
            status            ENUM ('active', 'passed', 'failed', 'expired')     DEFAULT 'active',
            INDEX idx_kingdom_id (kingdom_id),
            INDEX idx_status (status),
            INDEX idx_expires_at (expires_at),
            UNIQUE (kingdom_id, target, vote_type)
        );
    -- # }

    -- # { load
        SELECT *
        FROM kingdom_votes;
    -- # }

    -- # { get
    -- # :id int
        SELECT id,
               kingdom_id,
               vote_type,
               target,
               proposed_by,
               reason,
               votes_for,
               votes_against,
               created_at,
               expires_at,
               status,
               sanction_duration
        FROM kingdom_votes
        WHERE id = :id;
    -- # }

    -- # { create
    -- # :kingdom_id string
    -- # :vote_type string
    -- # :target string
    -- # :proposed_by string
    -- # :reason string
    -- # :sanction_duration int
    -- # :expires_at int
        INSERT INTO kingdom_votes(kingdom_id, vote_type, target, proposed_by, reason, sanction_duration, expires_at)
        VALUES (:kingdom_id, :vote_type, :target, :proposed_by, :reason, :sanction_duration, :expires_at)
        ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
    -- # }

    -- # { getActive
    -- # :kingdom_id string
        SELECT *
        FROM kingdom_votes
        WHERE kingdom_id = :kingdom_id
          AND status = 'active'
          AND expires_at > UNIX_TIMESTAMP()
        ORDER BY created_at DESC;
    -- # }

    -- # { vote
    -- # :vote_id int
    -- # :voter_uuid string
    -- # :voter_name string
    -- # :vote_for int
        INSERT INTO kingdom_vote_votes(vote_id, voter_uuid, voter_name, vote_for)
        VALUES (:vote_id, :voter_uuid, :voter_name, :vote_for)
        ON DUPLICATE KEY UPDATE vote_for = VALUES(vote_for);
    -- # }

    -- # { updateStatus
    -- # :id int
    -- # :status string
        UPDATE kingdom_votes
        SET status = :status
        WHERE id = :id;
    -- # }

    -- # { updateVotes
    -- # :id int
    -- # :votesFor int
    -- # :votesAgainst int
        UPDATE kingdom_votes
        SET votes_for     = :votesFor,
            votes_against = :votesAgainst
        WHERE id = :id;
    -- # }

    -- # { deleteExpired
        DELETE
        FROM kingdom_votes
        WHERE expires_at < UNIX_TIMESTAMP();
    -- # }
-- #}
