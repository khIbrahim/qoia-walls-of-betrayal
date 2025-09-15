-- #! mysql
-- # { store_member_sessions
    -- # { init
        CREATE TABLE IF NOT EXISTS store_member_sessions(
            id INT AUTO_INCREMENT,
            member_uuid VARCHAR(36) NOT NULL,
            clock_in_time TIMESTAMP NOT NULL,
            clock_out_time TIMESTAMP NULL,
            sales_this_session DECIMAL(10,2) DEFAULT 0.00,
            transactions_this_session INT DEFAULT 0,
            activities_log JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            INDEX idx_member_uuid(member_uuid),
            INDEX idx_clock_in_time(clock_in_time),
            INDEX idx_active_session(member_uuid, clock_out_time),
            INDEX idx_sales(sales_this_session),
            
            FOREIGN KEY (member_uuid) REFERENCES store_members(uuid) ON DELETE CASCADE
        );
    -- # }

    -- # { start
    -- # :member_uuid string
    -- # :clock_in_time string
    -- # :sales_this_session float
    -- # :transactions_this_session int
    -- # :activities_log string
        INSERT INTO store_member_sessions(
            member_uuid, clock_in_time, sales_this_session, 
            transactions_this_session, activities_log
        ) VALUES (
            :member_uuid, :clock_in_time, :sales_this_session,
            :transactions_this_session, :activities_log
        );
    -- # }

    -- # { end
    -- # :member_uuid string
    -- # :clock_out_time string
    -- # :sales_this_session float
    -- # :transactions_this_session int
        UPDATE store_member_sessions SET 
            clock_out_time = :clock_out_time,
            sales_this_session = :sales_this_session,
            transactions_this_session = :transactions_this_session
        WHERE member_uuid = :member_uuid 
        AND clock_out_time IS NULL;
    -- # }

    -- # { get_active
    -- # :member_uuid string
        SELECT * FROM store_member_sessions 
        WHERE member_uuid = :member_uuid 
        AND clock_out_time IS NULL
        ORDER BY clock_in_time DESC 
        LIMIT 1;
    -- # }

    -- # { get_all_active
        SELECT s.*, m.username 
        FROM store_member_sessions s
        JOIN store_members m ON s.member_uuid = m.uuid
        WHERE s.clock_out_time IS NULL
        ORDER BY s.clock_in_time DESC;
    -- # }

    -- # { get_history
    -- # :member_uuid string
    -- # :limit int
        SELECT * FROM store_member_sessions 
        WHERE member_uuid = :member_uuid 
        ORDER BY clock_in_time DESC 
        LIMIT :limit;
    -- # }

    -- # { update_session_stats
    -- # :member_uuid string
    -- # :sales_this_session float
    -- # :transactions_this_session int
    -- # :activities_log string
        UPDATE store_member_sessions SET 
            sales_this_session = :sales_this_session,
            transactions_this_session = :transactions_this_session,
            activities_log = :activities_log
        WHERE member_uuid = :member_uuid 
        AND clock_out_time IS NULL;
    -- # }

    -- # { get_daily_stats
    -- # :date string
        SELECT 
            m.username,
            s.member_uuid,
            SUM(s.sales_this_session) as daily_sales,
            SUM(s.transactions_this_session) as daily_transactions,
            SUM(TIMESTAMPDIFF(SECOND, s.clock_in_time, COALESCE(s.clock_out_time, NOW()))) / 3600 as hours_worked
        FROM store_member_sessions s
        JOIN store_members m ON s.member_uuid = m.uuid
        WHERE DATE(s.clock_in_time) = :date
        GROUP BY s.member_uuid, m.username
        ORDER BY daily_sales DESC;
    -- # }

    -- # { get_weekly_stats
    -- # :start_date string
    -- # :end_date string
        SELECT 
            m.username,
            s.member_uuid,
            SUM(s.sales_this_session) as weekly_sales,
            SUM(s.transactions_this_session) as weekly_transactions,
            SUM(TIMESTAMPDIFF(SECOND, s.clock_in_time, COALESCE(s.clock_out_time, NOW()))) / 3600 as hours_worked
        FROM store_member_sessions s
        JOIN store_members m ON s.member_uuid = m.uuid
        WHERE DATE(s.clock_in_time) BETWEEN :start_date AND :end_date
        GROUP BY s.member_uuid, m.username
        ORDER BY weekly_sales DESC;
    -- # }

    -- # { cleanup_old_sessions
    -- # :days_old int
        DELETE FROM store_member_sessions 
        WHERE clock_in_time < DATE_SUB(NOW(), INTERVAL :days_old DAY);
    -- # }
-- # }