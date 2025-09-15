-- #! mysql
-- # { store_member_shifts
    -- # { init
        CREATE TABLE IF NOT EXISTS store_member_shifts(
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_uuid VARCHAR(36) NOT NULL,
            store_id VARCHAR(64) NOT NULL,
            shift_start TIMESTAMP NOT NULL,
            shift_end TIMESTAMP NULL,
            break_duration INT DEFAULT 0, -- in minutes
            sales_during_shift DECIMAL(15, 2) DEFAULT 0.00,
            commission_earned DECIMAL(10, 2) DEFAULT 0.00,
            notes TEXT,
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_member_uuid(member_uuid),
            INDEX idx_store_id(store_id),
            INDEX idx_shift_start(shift_start),
            INDEX idx_status(status),
            FOREIGN KEY (member_uuid, store_id) REFERENCES store_members(uuid, store_id) ON DELETE CASCADE
        );
    -- # }

    -- # { startShift
    -- # :member_uuid string
    -- # :store_id string
    -- # :shift_start int
    -- # :notes string
        INSERT INTO store_member_shifts(member_uuid, store_id, shift_start, notes)
        VALUES (:member_uuid, :store_id, FROM_UNIXTIME(:shift_start), :notes);
    -- # }

    -- # { endShift
    -- # :id int
    -- # :shift_end int
    -- # :break_duration int
    -- # :sales_during_shift float
    -- # :commission_earned float
        UPDATE store_member_shifts 
        SET shift_end = FROM_UNIXTIME(:shift_end),
            break_duration = :break_duration,
            sales_during_shift = :sales_during_shift,
            commission_earned = :commission_earned,
            status = 'completed'
        WHERE id = :id;
    -- # }

    -- # { getActiveShift
    -- # :member_uuid string
    -- # :store_id string
        SELECT * FROM store_member_shifts 
        WHERE member_uuid = :member_uuid 
        AND store_id = :store_id 
        AND status = 'active'
        ORDER BY shift_start DESC 
        LIMIT 1;
    -- # }

    -- # { getMemberShifts
    -- # :member_uuid string
    -- # :store_id string
    -- # :limit int
    -- # :offset int
        SELECT * FROM store_member_shifts 
        WHERE member_uuid = :member_uuid AND store_id = :store_id
        ORDER BY shift_start DESC
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { getStoreShifts
    -- # :store_id string
    -- # :start_date int
    -- # :end_date int
    -- # :limit int
    -- # :offset int
        SELECT s.*, m.username, m.role
        FROM store_member_shifts s
        JOIN store_members m ON s.member_uuid = m.uuid AND s.store_id = m.store_id
        WHERE s.store_id = :store_id
        AND s.shift_start >= FROM_UNIXTIME(:start_date)
        AND s.shift_start <= FROM_UNIXTIME(:end_date)
        ORDER BY s.shift_start DESC
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { getShiftStats
    -- # :member_uuid string
    -- # :store_id string
    -- # :start_date int
    -- # :end_date int
        SELECT 
            COUNT(*) as total_shifts,
            SUM(TIMESTAMPDIFF(HOUR, shift_start, shift_end)) as total_hours,
            SUM(break_duration) as total_break_minutes,
            SUM(sales_during_shift) as total_sales,
            SUM(commission_earned) as total_commission,
            AVG(sales_during_shift) as avg_sales_per_shift
        FROM store_member_shifts 
        WHERE member_uuid = :member_uuid 
        AND store_id = :store_id
        AND status = 'completed'
        AND shift_start >= FROM_UNIXTIME(:start_date)
        AND shift_start <= FROM_UNIXTIME(:end_date);
    -- # }

    -- # { cancelShift
    -- # :id int
        UPDATE store_member_shifts 
        SET status = 'cancelled'
        WHERE id = :id;
    -- # }

    -- # { updateShiftSales
    -- # :id int
    -- # :sales_amount float
    -- # :commission_amount float
        UPDATE store_member_shifts 
        SET sales_during_shift = sales_during_shift + :sales_amount,
            commission_earned = commission_earned + :commission_amount
        WHERE id = :id AND status = 'active';
    -- # }

-- # }