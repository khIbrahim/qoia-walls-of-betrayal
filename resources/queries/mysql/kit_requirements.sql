-- #! mysql
-- # { kit_requirements
    -- # { init
        CREATE TABLE IF NOT EXISTS kit_requirement (
            id INTEGER NOT NULL,
            kingdom_id VARCHAR(64) NOT NULL,
            kit_id VARCHAR(64) NOT NULL,
            amount INTEGER DEFAULT 0 NOT NULL,
            PRIMARY KEY (id, kingdom_id, kit_id)
        );
    -- # }

    -- # { insert
    -- # :id int
    -- # :kingdom string
    -- # :kit string
        INSERT INTO kit_requirement(id, kingdom_id, kit_id) VALUES (:id, :kingdom, :kit);
    -- # }

    -- # { getByKingdomAndKit
    -- # :kingdom string
    -- # :kit string
        SELECT * FROM kit_requirement WHERE kingdom_id = :kingdom AND kit_id = :kit;
    -- # }

    -- # { increment
    -- # :id int
    -- # :kingdom string
    -- # :kit string
    -- # :progress int
    -- # :max int
        UPDATE kit_requirement
        SET
            amount = MIN(amount + :progress, :max)
        WHERE kingdom_id = :kingdom AND kit_id = :kit AND id = :id;
    -- # }
-- #}
