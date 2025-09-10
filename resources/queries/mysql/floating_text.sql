-- #! mysql
-- # { floatingText
    -- # { init
        CREATE TABLE IF NOT EXISTS floating_text(
            id VARCHAR(32) PRIMARY KEY,
            text LONGTEXT,
            pos JSON
        );
    -- # }

    -- # { load
        SELECT * from floating_text;
    -- # }

    -- # { create
    -- # :id string
    -- # :text string
    -- # :pos string
        INSERT INTO floating_text(id, text, pos)
        VALUES (:id, :text, :pos);
    -- # }

    -- # { remove
    -- # :id string
        DELETE FROM floating_text
        WHERE id = :id;
    -- # }

    -- # { updateText
    -- # :id string
    -- # :text string
        UPDATE floating_text
        SET text = :text
        WHERE id = :id;
    -- # }
-- # }
