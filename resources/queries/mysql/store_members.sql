-- #! mysql
-- # { store_members
    -- # { init
        CREATE TABLE IF NOT EXISTS store_members(
            uuid VARCHAR(36) NOT NULL,
            username VARCHAR(32) NOT NULL,
            role ENUM('owner', 'manager', 'supervisor', 'cashier', 'employee', 'trainee') NOT NULL DEFAULT 'trainee',
            status ENUM('active', 'inactive', 'suspended', 'on_break', 'terminated') NOT NULL DEFAULT 'active',
            hired_at TIMESTAMP NOT NULL,
            last_login TIMESTAMP NULL,
            total_working_hours INT DEFAULT 0,
            total_transactions INT DEFAULT 0,
            total_sales DECIMAL(10,2) DEFAULT 0.00,
            permissions JSON,
            store_id VARCHAR(64) NULL,
            last_promotion_at TIMESTAMP NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (uuid),
            INDEX idx_uuid(uuid),
            INDEX idx_username(username),
            INDEX idx_role(role),
            INDEX idx_status(status),
            INDEX idx_store_id(store_id),
            INDEX idx_total_sales(total_sales),
            INDEX idx_total_transactions(total_transactions)
        );
    -- # }

    -- # { load_by_uuid
    -- # :uuid string
        SELECT * FROM store_members WHERE uuid = :uuid;
    -- # }

    -- # { load_by_username
    -- # :username string
        SELECT * FROM store_members WHERE username = :username;
    -- # }

    -- # { insert
    -- # :uuid string
    -- # :username string
    -- # :role string
    -- # :status string
    -- # :hired_at string
    -- # :last_login string
    -- # :total_working_hours int
    -- # :total_transactions int
    -- # :total_sales float
    -- # :permissions string
    -- # :store_id string
    -- # :last_promotion_at string
    -- # :notes string
        INSERT INTO store_members(
            uuid, username, role, status, hired_at, last_login, 
            total_working_hours, total_transactions, total_sales, 
            permissions, store_id, last_promotion_at, notes
        ) VALUES (
            :uuid, :username, :role, :status, :hired_at, :last_login,
            :total_working_hours, :total_transactions, :total_sales,
            :permissions, :store_id, :last_promotion_at, :notes
        );
    -- # }

    -- # { update
    -- # :uuid string
    -- # :username string
    -- # :role string
    -- # :status string
    -- # :last_login string
    -- # :total_working_hours int
    -- # :total_transactions int
    -- # :total_sales float
    -- # :permissions string
    -- # :store_id string
    -- # :last_promotion_at string
    -- # :notes string
        UPDATE store_members SET 
            username = :username,
            role = :role,
            status = :status,
            last_login = :last_login,
            total_working_hours = :total_working_hours,
            total_transactions = :total_transactions,
            total_sales = :total_sales,
            permissions = :permissions,
            store_id = :store_id,
            last_promotion_at = :last_promotion_at,
            notes = :notes
        WHERE uuid = :uuid;
    -- # }

    -- # { delete
    -- # :uuid string
        DELETE FROM store_members WHERE uuid = :uuid;
    -- # }

    -- # { get_all
        SELECT * FROM store_members ORDER BY hired_at DESC;
    -- # }

    -- # { get_by_role
    -- # :role string
        SELECT * FROM store_members WHERE role = :role ORDER BY hired_at DESC;
    -- # }

    -- # { get_by_status
    -- # :status string
        SELECT * FROM store_members WHERE status = :status ORDER BY hired_at DESC;
    -- # }

    -- # { get_by_store_id
    -- # :store_id string
        SELECT * FROM store_members WHERE store_id = :store_id OR (:store_id IS NULL AND store_id IS NULL) ORDER BY hired_at DESC;
    -- # }

    -- # { update_last_login
    -- # :uuid string
    -- # :last_login string
        UPDATE store_members SET last_login = :last_login WHERE uuid = :uuid;
    -- # }

    -- # { update_work_stats
    -- # :uuid string
    -- # :additional_hours int
    -- # :additional_sales float
    -- # :additional_transactions int
        UPDATE store_members SET 
            total_working_hours = total_working_hours + :additional_hours,
            total_sales = total_sales + :additional_sales,
            total_transactions = total_transactions + :additional_transactions
        WHERE uuid = :uuid;
    -- # }

    -- # { get_top_by_sales
    -- # :limit int
        SELECT * FROM store_members 
        WHERE status = 'active' 
        ORDER BY total_sales DESC 
        LIMIT :limit;
    -- # }

    -- # { get_top_by_transactions
    -- # :limit int
        SELECT * FROM store_members 
        WHERE status = 'active' 
        ORDER BY total_transactions DESC 
        LIMIT :limit;
    -- # }
-- # }