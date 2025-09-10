-- #! mysql
    -- # { player_inventories
    -- # { init
        CREATE TABLE IF NOT EXISTS player_inventories(
            uuid VARCHAR(36) NOT NULL,
            name VARCHAR(64) NOT NULL,
            context VARCHAR(32) NOT NULL,
            inventory BLOB,
            armor BLOB,
            offhand BLOB,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (uuid, name),
            INDEX idx_uuid(uuid),
            INDEX idx_name(name)
        );
    -- # }

    -- # { save
    -- # :uuid string
    -- # :name string
    -- # :context string
    -- # :inventory string
    -- # :armor string
    -- # :offhand string
        INSERT INTO player_inventories(uuid, name, context, inventory, armor, offhand)
        VALUES (:uuid, :name, :context, :inventory, :armor, :offhand)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            inventory = VALUES(inventory),
            armor = VALUES(armor),
            offhand = VALUES(offhand);
        -- # }

    -- # { load
    -- # :uuid string
    -- # :context string
        SELECT
            inventory,
            armor,
            offhand
        FROM player_inventories
        WHERE uuid = :uuid AND context = :context;
    -- # }

-- # }