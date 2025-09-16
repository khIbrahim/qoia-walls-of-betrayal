-- #! mysql
-- # { kills

    -- # { init
        CREATE TABLE IF NOT EXISTS kills(
            id BIGINT NOT NULL AUTO_INCREMENT,
            killer VARCHAR(64) NOT NULL,
            victim VARCHAR(64) NOT NULL,
            weapon VARCHAR(64) NOT NULL,
            opponents JSON,
            killer_health INT NOT NULL,
            teamkill TINYINT(1) NOT NULL,
            in_kingdom TINYINT(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            INDEX idx_killer (killer),
            INDEX idx_victim (victim),
            INDEX idx_created_at (created_at)
        );
    -- # }

    -- # { log
    -- # :killer string
    -- # :victim string
    -- # :weapon string
    -- # :opponents string
    -- # :killer_health int
    -- # :teamkill int
    -- # :in_kingdom int
        INSERT INTO kills(killer, victim, weapon, opponents, killer_health, teamkill, in_kingdom)
        VALUES (:killer, :victim, :weapon, :opponents, :killer_health, :teamkill, :in_kingdom);
    -- # }

-- # }