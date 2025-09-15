-- #! mysql
-- # { store_members
    -- # { init
        CREATE TABLE IF NOT EXISTS store_members(
            id INT AUTO_INCREMENT PRIMARY KEY,
            uuid VARCHAR(36) NOT NULL,
            username VARCHAR(32) NOT NULL,
            store_id VARCHAR(64) NOT NULL,
            role ENUM('owner', 'manager', 'cashier', 'staff', 'intern') NOT NULL DEFAULT 'staff',
            permissions JSON,
            salary DECIMAL(10, 2) DEFAULT 0.00,
            commission_rate DECIMAL(5, 2) DEFAULT 0.00,
            total_sales DECIMAL(15, 2) DEFAULT 0.00,
            total_commission DECIMAL(15, 2) DEFAULT 0.00,
            hire_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            last_shift_start TIMESTAMP NULL,
            last_shift_end TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            UNIQUE KEY unique_member_store (uuid, store_id),
            INDEX idx_uuid(uuid),
            INDEX idx_store_id(store_id),
            INDEX idx_role(role),
            INDEX idx_active(is_active),
            INDEX idx_username(username)
        );
    -- # }

    -- # { create
    -- # :uuid string
    -- # :username string
    -- # :store_id string
    -- # :role string
    -- # :permissions string
    -- # :salary float
    -- # :commission_rate float
        INSERT INTO store_members(uuid, username, store_id, role, permissions, salary, commission_rate)
        VALUES (:uuid, :username, :store_id, :role, :permissions, :salary, :commission_rate);
    -- # }

    -- # { get
    -- # :uuid string
    -- # :store_id string
        SELECT * FROM store_members WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { getByUuid
    -- # :uuid string
        SELECT * FROM store_members WHERE uuid = :uuid;
    -- # }

    -- # { getByStore
    -- # :store_id string
    -- # :limit int
    -- # :offset int
        SELECT * FROM store_members 
        WHERE store_id = :store_id 
        ORDER BY role, hire_date DESC
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { getActiveByStore
    -- # :store_id string
        SELECT * FROM store_members 
        WHERE store_id = :store_id AND is_active = TRUE
        ORDER BY role, hire_date DESC;
    -- # }

    -- # { updateRole
    -- # :uuid string
    -- # :store_id string
    -- # :role string
    -- # :permissions string
        UPDATE store_members 
        SET role = :role, permissions = :permissions
        WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { updateSalary
    -- # :uuid string
    -- # :store_id string
    -- # :salary float
    -- # :commission_rate float
        UPDATE store_members 
        SET salary = :salary, commission_rate = :commission_rate
        WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { updateActivity
    -- # :uuid string
    -- # :store_id string
    -- # :is_active bool
        UPDATE store_members 
        SET is_active = :is_active
        WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { addSale
    -- # :uuid string
    -- # :store_id string
    -- # :sale_amount float
    -- # :commission_amount float
        UPDATE store_members 
        SET total_sales = total_sales + :sale_amount,
            total_commission = total_commission + :commission_amount
        WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { startShift
    -- # :uuid string
    -- # :store_id string
    -- # :start_time int
        UPDATE store_members 
        SET last_shift_start = FROM_UNIXTIME(:start_time)
        WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { endShift
    -- # :uuid string
    -- # :store_id string
    -- # :end_time int
        UPDATE store_members 
        SET last_shift_end = FROM_UNIXTIME(:end_time)
        WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { getTopSellers
    -- # :store_id string
    -- # :limit int
        SELECT uuid, username, role, total_sales, total_commission
        FROM store_members 
        WHERE store_id = :store_id AND is_active = TRUE
        ORDER BY total_sales DESC
        LIMIT :limit;
    -- # }

    -- # { searchMembers
    -- # :store_id string
    -- # :search_term string
    -- # :limit int
    -- # :offset int
        SELECT * FROM store_members 
        WHERE store_id = :store_id 
        AND (username LIKE CONCAT('%', :search_term, '%') OR role LIKE CONCAT('%', :search_term, '%'))
        ORDER BY role, hire_date DESC
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { delete
    -- # :uuid string
    -- # :store_id string
        DELETE FROM store_members WHERE uuid = :uuid AND store_id = :store_id;
    -- # }

    -- # { getStoreStats
    -- # :store_id string
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_members,
            SUM(total_sales) as total_store_sales,
            SUM(total_commission) as total_commissions,
            AVG(salary) as average_salary
        FROM store_members 
        WHERE store_id = :store_id;
    -- # }

-- # }