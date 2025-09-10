-- #! mysql
-- # { economy
    -- # { init
        CREATE TABLE IF NOT EXISTS economy(
            uuid VARCHAR(36) PRIMARY KEY,
            username VARCHAR(32) NOT NULL,
            amount DECIMAL(64, 2) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_uuid(uuid),
            INDEX idx_username(username)
        );
    -- # }

    -- # { get
    -- # :uuid ?string
    -- # :name ?string
        SELECT
            e.*,
            DENSE_RANK() OVER (ORDER BY e.amount DESC) AS position
        FROM economy e
        WHERE e.uuid = :uuid OR e.username = :name;
    -- # }

    -- # { insert
    -- # :uuid string
    -- # :name string
        INSERT IGNORE INTO economy(uuid, username) VALUES (:uuid, :name);
    -- # }

    -- # { add
    -- # :uuid ?string
    -- # :name ?string
    -- # :amount int
        UPDATE economy
        SET amount = amount + :amount
        WHERE (uuid = :uuid OR username = :name);
    -- # }

    -- # { subtract
    -- # :uuid ?string
    -- # :name ?string
    -- # :amount int
        UPDATE economy
        SET amount = amount - :amount
        WHERE (uuid = :uuid OR username = :name) AND amount >= :amount;
    -- # }

    -- # { transfer

        -- # { begin
            BEGIN;
        -- # }

        -- # { debitSender
        -- # :s_uuid string
        -- # :s_name ?string
        -- # :amount int
            UPDATE economy
            SET amount = amount - :amount
            WHERE (uuid = :s_uuid OR username = :s_name)
              AND amount >= :amount;
        -- # }

        -- # { creditReceiver
        -- # :r_uuid string
        -- # :r_name ?string
        -- # :amount int
            UPDATE economy
            SET amount = amount + :amount
            WHERE (uuid = :r_uuid OR username = :r_name);
        -- # }

        -- # { commit
            COMMIT;
        -- # }

        -- # { rollback
            ROLLBACK;
        -- # }

    -- # }

    -- # { top
    -- # :limit int
    -- # :offset int
        SELECT
            e.username,
            e.uuid,
            e.amount,
            DENSE_RANK() OVER (ORDER BY e.amount DESC) AS position
        FROM economy e
        ORDER BY e.amount DESC, e.username
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { set
    -- # :name string # pour le moment ça va marcher seulement avec le name
    -- # :amount int
        UPDATE economy
        SET amount = :amount
        WHERE username = :name;
    -- # }

-- #}
