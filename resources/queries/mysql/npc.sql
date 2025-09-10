-- #! mysql
-- # { npc
    -- # { init
        CREATE TABLE IF NOT EXISTS npc(
            id VARCHAR(36) PRIMARY KEY NOT NULL,
            name LONGTEXT,
            command VARCHAR(255),
            cooldown INTEGER DEFAULT 1,
            pos JSON,
            yaw FLOAT,
            pitch FLOAT,
            skin BLOB,
            skin_id VARCHAR(255),
            cape BLOB,
            geometry_name VARCHAR(255),
            geometry BLOB
        );
    -- # }

    -- # { create
    -- # :id string
    -- # :name string
    -- # :command string
    -- # :cooldown string
    -- # :pos string
    -- # :yaw float
    -- # :pitch float
    -- # :skin string
    -- # :skin_id string
    -- # :cape string
    -- # :geometry_name string
    -- # :geometry string
        INSERT INTO npc(id, name, command, cooldown, pos, yaw, pitch, skin, skin_id, cape, geometry_name, geometry)
        VALUES (:id, :name, :command, :cooldown, :pos, :yaw, :pitch, :skin, :skin_id, :cape, :geometry_name, :geometry);
    -- # }

    -- # { update
    -- # :id string
    -- # :name string
    -- # :command string
    -- # :cooldown string
    -- # :pos string
    -- # :yaw float
    -- # :pitch float
    -- # :skin string
    -- # :skin_id string
    -- # :cape string
    -- # :geometry_name string
    -- # :geometry string
        UPDATE npc
        SET name          = :name,
            command       = :command,
            cooldown      = :cooldown,
            pos           = :pos,
            yaw           = :yaw,
            pitch         = :pitch,
            skin          = :skin,
            skin_id       = :skin_id,
            cape          = :cape,
            geometry_name = :geometry_name,
            geometry      = :geometry
        WHERE id = :id;
    -- # }

    -- # { delete
    -- # :id string
        DELETE FROM npc WHERE id = :id;
    -- # }

    -- # { loadAll
        SELECT * FROM npc;
    -- # }
-- # }
