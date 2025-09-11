-- #! mysql
-- # { seasons
    -- # { init
        CREATE TABLE IF NOT EXISTS seasons(
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            season_number INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            theme VARCHAR(100) NULL,
            start_time INTEGER NOT NULL,
            planned_end_time INTEGER NOT NULL,
            actual_end_time INTEGER NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            properties TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            UNIQUE KEY unique_season_number (season_number),
            UNIQUE (name)
        );
    -- # }

    -- # { loadCurrent
        SELECT * FROM seasons
        WHERE is_active = 1
        LIMIT 1;
    -- # }

    -- # { loadById
    -- # :id int
        SELECT * FROM seasons
        WHERE id = :id
        LIMIT 1;
    -- # }

    -- # { loadHistory
        SELECT * FROM seasons
        WHERE is_active = 0
        ORDER BY season_number DESC;
    -- # }

    -- # { insert
    -- # :season_number int
    -- # :name string
    -- # :description ?string
    -- # :theme ?string
    -- # :start_time int
    -- # :planned_end_time int
    -- # :actual_end_time ?int
    -- # :is_active int
    -- # :properties string
        INSERT INTO seasons(season_number, name, description, theme, start_time, planned_end_time, actual_end_time, is_active, properties)
        VALUES(:season_number, :name, :description, :theme, :start_time, :planned_end_time, :actual_end_time, :is_active, :properties);
    -- # }

    -- # { update
    -- # :id int
    -- # :season_number int
    -- # :name string
    -- # :description ?string
    -- # :theme ?string
    -- # :start_time int
    -- # :planned_end_time int
    -- # :actual_end_time ?int
    -- # :is_active int
    -- # :properties string
        UPDATE seasons
        SET season_number = :season_number,
            name = :name,
            description = :description,
            theme = :theme,
            start_time = :start_time,
            planned_end_time = :planned_end_time,
            actual_end_time = :actual_end_time,
            is_active = :is_active,
            properties = :properties,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id;
    -- # }

-- # }

-- # { season_players
    -- # { init
        CREATE TABLE IF NOT EXISTS season_players(
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            season_id INTEGER NOT NULL,
            player_uuid VARCHAR(36) NOT NULL,
            points INTEGER DEFAULT 0,
            kills INTEGER DEFAULT 0,
            deaths INTEGER DEFAULT 0,
            rewards_claimed JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_season_player (season_id, player_uuid),
            FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
            FOREIGN KEY (player_uuid) REFERENCES players(uuid) ON DELETE CASCADE
        );
    -- # }

    -- # { load
    -- # :season_id int
    -- # :player_uuid string
        SELECT * FROM season_players
        WHERE season_id = :season_id AND player_uuid = :player_uuid
        LIMIT 1;
    -- # }

    -- # { insert
    -- # :season_id int
    -- # :player_uuid string
        INSERT INTO season_players(season_id, player_uuid)
        VALUES(:season_id, :player_uuid)
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
    -- # }

    -- # { updateStats
    -- # :season_id int
    -- # :player_uuid string
    -- # :points int
    -- # :kills int
    -- # :deaths int
        INSERT INTO season_players(season_id, player_uuid, points, kills, deaths)
        VALUES(:season_id, :player_uuid, :points, :kills, :deaths)
        ON DUPLICATE KEY UPDATE
            points = points + :points,
            kills = kills + :kills,
            deaths = deaths + :deaths,
            updated_at = CURRENT_TIMESTAMP;
    -- # }
-- # }

-- # { season_kingdoms
    -- # { init
        CREATE TABLE IF NOT EXISTS season_kingdoms(
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            season_id INTEGER NOT NULL,
            kingdom_id VARCHAR(64) NOT NULL,
            points INTEGER DEFAULT 0,
            ranking INTEGER DEFAULT 0,
            wins INTEGER DEFAULT 0,
            losses INTEGER DEFAULT 0,
            rewards_claimed JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_season_kingdom (season_id, kingdom_id),
            FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
            FOREIGN KEY (kingdom_id) REFERENCES kingdoms(id) ON DELETE CASCADE
        );
    -- # }

    -- # { load
    -- # :season_id int
    -- # :kingdom_id string
        SELECT * FROM season_kingdoms
        WHERE season_id = :season_id AND kingdom_id = :kingdom_id
        LIMIT 1;
    -- # }

    -- # { insert
    -- # :season_id int
    -- # :kingdom_id string
        INSERT INTO season_kingdoms(season_id, kingdom_id)
        VALUES(:season_id, :kingdom_id)
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
    -- # }

    -- # { updateStats
    -- # :season_id int
    -- # :kingdom_id string
    -- # :points int
    -- # :ranking int
    -- # :wins int
    -- # :losses int
    -- # :rewards_claimed string
        INSERT INTO season_kingdoms(season_id, kingdom_id, points, ranking, wins, losses, rewards_claimed)
        VALUES(:season_id, :kingdom_id, :points, :ranking, :wins, :losses, :rewards_claimed)
        ON DUPLICATE KEY UPDATE
            points = :points,
            ranking = :ranking,
            wins = :wins,
            losses = :losses,
            rewards_claimed = :rewards_claimed,
            updated_at = CURRENT_TIMESTAMP;
    -- # }

    -- # { getRankings
    -- # :season_id int
        SELECT * FROM season_kingdoms
        WHERE season_id = :season_id
        ORDER BY points DESC, wins DESC, (wins / GREATEST(wins + losses, 1)) DESC
        LIMIT 100;
    -- # }
-- # }