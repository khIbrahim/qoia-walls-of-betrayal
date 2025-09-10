-- #! mysql
-- # { kingdom_vote_votes
    -- # { init
        CREATE TABLE IF NOT EXISTS kingdom_vote_votes
        (
            vote_id    INT         NOT NULL,
            voter_uuid VARCHAR(36) NOT NULL,
            voter_name VARCHAR(36) NOT NULL,
            vote_for   TINYINT(1)  NOT NULL,
            voted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (vote_id, voter_uuid),
            INDEX idx_vote_id (vote_id),
            INDEX idx_voter_uuid (voter_uuid),
            UNIQUE (voter_uuid, vote_id),
            UNIQUE (voter_name, vote_id)
        );
    -- # }

    -- # { count
    -- # :id int
        SELECT SUM(CASE WHEN vote_for = 1 THEN 1 ELSE 0 END) AS votes_for,
               SUM(CASE WHEN vote_for = 0 THEN 1 ELSE 0 END) AS votes_against,
               COUNT(*)                                      AS total
        FROM kingdom_vote_votes
        WHERE vote_id = :id;
    -- # }

    -- # { getVoterChoice
    -- # :id int
    -- # :name string
        SELECT vote_for
        FROM kingdom_vote_votes
        WHERE vote_id = :id
          AND voter_name = :name;
    -- # }
-- # }
