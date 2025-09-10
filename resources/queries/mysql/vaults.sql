-- #! mysql
-- # { vaults
    -- # { init
        CREATE TABLE IF NOT EXISTS vaults(
            uuid VARCHAR(36) NOT NULL,
            name VARCHAR(64) NOT NULL,
            number TINYINT UNSIGNED NOT NULL,
            items BLOB NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (uuid, name, number),
            INDEX idx_uuid(uuid),
            INDEX idx_name(name)
        );
    -- # }

    -- # { open
    -- # :uuid ?string
    -- # :username ?string
    -- # :number int
        SELECT items FROM vaults
        WHERE (uuid = :uuid OR name = :username) AND number = :number;
    -- # }

    -- # { close
    -- # :uuid ?string
    -- # :username ?string
    -- # :number int
    -- # :items string
        INSERT INTO vaults(uuid, name, number, items)
        VALUES (:uuid, :username, :number, :items)
        ON DUPLICATE KEY UPDATE
            items = VALUES(items);
    -- # }
-- # }
