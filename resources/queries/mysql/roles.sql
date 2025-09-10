-- #! mysql
-- # { roles
    -- # { init
        CREATE TABLE IF NOT EXISTS player_roles(
            uuid VARCHAR(36) NOT NULL PRIMARY KEY,
            username VARCHAR(64),
            role_id VARCHAR(64) NOT NULL,
            subRoles JSON,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at BIGINT DEFAULT NULL,
            permissions JSON,
            INDEX idx_role_id(role_id),
            INDEX idx_expires_at(expires_at),
            INDEX idx_username(username),
            INDEX idx_uuid(uuid)
        );
    -- # }

    -- # { assign
    -- # :uuid string
    -- # :username ?string
    -- # :role_id string
    -- # :subRoles ?string
    -- # :permissions ?string
    -- # :expires_at ?int
        INSERT INTO player_roles(uuid, role_id, expires_at, username, subRoles, permissions)
        VALUES (:uuid, :role_id, :expires_at, :username, :subRoles, :permissions)
        ON DUPLICATE KEY UPDATE
            role_id = VALUES(role_id),
            assigned_at = CURRENT_TIMESTAMP,
            expires_at = VALUES(expires_at);
    -- # }

    -- # { get
    -- # :uuid ?string
    -- # :username ?string
        SELECT * FROM player_roles WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { remove
    -- # :uuid string
        DELETE FROM player_roles WHERE uuid = :uuid;
    -- # }

    -- # { updateRole
    -- # :uuid ?string
    -- # :username ?string
    -- # :role_id string
    -- # :expires_at ?int
        UPDATE player_roles
        SET role_id = :role_id,
            expires_at = :expires_at,
            assigned_at = CURRENT_TIMESTAMP
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { getPermissions
    -- # :uuid ?string
    -- # :username ?string
        SELECT permissions FROM player_roles
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { updatePermissions
    -- # :uuid ?string
    -- # :username ?string
    -- # :permissions string
        UPDATE player_roles
        SET permissions = :permissions
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { getSubRoles
    -- # :uuid ?string
    -- # :username ?string
        SELECT subRoles FROM player_roles
        WHERE (uuid = :uuid OR username = :username);
    -- # }

    -- # { updateSubRoles
    -- # :uuid ?string
    -- # :username ?string
    -- # :subRoles string
        UPDATE player_roles
        SET subRoles = :subRoles
        WHERE (uuid = :uuid OR username = :username);
    -- # }
-- #}
