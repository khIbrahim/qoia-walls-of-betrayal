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