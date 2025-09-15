-- #! mysql
-- # { store_transactions
    -- # { init
        CREATE TABLE IF NOT EXISTS store_transactions(
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_uuid VARCHAR(36) NOT NULL UNIQUE,
            store_id VARCHAR(64) NOT NULL,
            member_uuid VARCHAR(36) NOT NULL,
            customer_uuid VARCHAR(36) NULL,
            transaction_type ENUM('sale', 'refund', 'void') NOT NULL DEFAULT 'sale',
            total_amount DECIMAL(10, 2) NOT NULL,
            commission_amount DECIMAL(10, 2) DEFAULT 0.00,
            items_sold JSON,
            payment_method ENUM('cash', 'card', 'digital', 'credit') DEFAULT 'cash',
            shift_id INT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_transaction_uuid(transaction_uuid),
            INDEX idx_store_id(store_id),
            INDEX idx_member_uuid(member_uuid),
            INDEX idx_customer_uuid(customer_uuid),
            INDEX idx_created_at(created_at),
            INDEX idx_transaction_type(transaction_type),
            FOREIGN KEY (member_uuid, store_id) REFERENCES store_members(uuid, store_id) ON DELETE CASCADE,
            FOREIGN KEY (shift_id) REFERENCES store_member_shifts(id) ON DELETE SET NULL
        );
    -- # }

    -- # { createTransaction
    -- # :transaction_uuid string
    -- # :store_id string
    -- # :member_uuid string
    -- # :customer_uuid ?string
    -- # :transaction_type string
    -- # :total_amount float
    -- # :commission_amount float
    -- # :items_sold string
    -- # :payment_method string
    -- # :shift_id ?int
    -- # :notes ?string
        INSERT INTO store_transactions(
            transaction_uuid, store_id, member_uuid, customer_uuid,
            transaction_type, total_amount, commission_amount,
            items_sold, payment_method, shift_id, notes
        ) VALUES (
            :transaction_uuid, :store_id, :member_uuid, :customer_uuid,
            :transaction_type, :total_amount, :commission_amount,
            :items_sold, :payment_method, :shift_id, :notes
        );
    -- # }

    -- # { getTransaction
    -- # :transaction_uuid string
        SELECT * FROM store_transactions WHERE transaction_uuid = :transaction_uuid;
    -- # }

    -- # { getMemberTransactions
    -- # :member_uuid string
    -- # :store_id string
    -- # :limit int
    -- # :offset int
        SELECT * FROM store_transactions 
        WHERE member_uuid = :member_uuid AND store_id = :store_id
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { getStoreTransactions
    -- # :store_id string
    -- # :start_date int
    -- # :end_date int
    -- # :limit int
    -- # :offset int
        SELECT t.*, m.username, m.role
        FROM store_transactions t
        JOIN store_members m ON t.member_uuid = m.uuid AND t.store_id = m.store_id
        WHERE t.store_id = :store_id
        AND t.created_at >= FROM_UNIXTIME(:start_date)
        AND t.created_at <= FROM_UNIXTIME(:end_date)
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset;
    -- # }

    -- # { getTransactionsByShift
    -- # :shift_id int
        SELECT * FROM store_transactions 
        WHERE shift_id = :shift_id
        ORDER BY created_at DESC;
    -- # }

    -- # { getMemberSalesStats
    -- # :member_uuid string
    -- # :store_id string
    -- # :start_date int
    -- # :end_date int
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN transaction_type = 'sale' THEN total_amount ELSE 0 END) as total_sales,
            SUM(CASE WHEN transaction_type = 'refund' THEN total_amount ELSE 0 END) as total_refunds,
            SUM(commission_amount) as total_commission,
            AVG(CASE WHEN transaction_type = 'sale' THEN total_amount ELSE NULL END) as avg_sale_amount,
            COUNT(CASE WHEN transaction_type = 'sale' THEN 1 END) as sales_count,
            COUNT(CASE WHEN transaction_type = 'refund' THEN 1 END) as refunds_count
        FROM store_transactions 
        WHERE member_uuid = :member_uuid 
        AND store_id = :store_id
        AND created_at >= FROM_UNIXTIME(:start_date)
        AND created_at <= FROM_UNIXTIME(:end_date);
    -- # }

    -- # { getStoreSalesStats
    -- # :store_id string
    -- # :start_date int
    -- # :end_date int
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN transaction_type = 'sale' THEN total_amount ELSE 0 END) as total_sales,
            SUM(CASE WHEN transaction_type = 'refund' THEN total_amount ELSE 0 END) as total_refunds,
            SUM(commission_amount) as total_commission,
            AVG(CASE WHEN transaction_type = 'sale' THEN total_amount ELSE NULL END) as avg_sale_amount,
            COUNT(DISTINCT member_uuid) as active_sellers
        FROM store_transactions 
        WHERE store_id = :store_id
        AND created_at >= FROM_UNIXTIME(:start_date)
        AND created_at <= FROM_UNIXTIME(:end_date);
    -- # }

    -- # { getTopSellingItems
    -- # :store_id string
    -- # :start_date int
    -- # :end_date int
    -- # :limit int
        SELECT 
            JSON_EXTRACT(items_sold, '$[*].id') as item_id,
            JSON_EXTRACT(items_sold, '$[*].name') as item_name,
            SUM(JSON_EXTRACT(items_sold, '$[*].quantity')) as total_quantity,
            SUM(JSON_EXTRACT(items_sold, '$[*].price') * JSON_EXTRACT(items_sold, '$[*].quantity')) as total_revenue
        FROM store_transactions 
        WHERE store_id = :store_id
        AND transaction_type = 'sale'
        AND created_at >= FROM_UNIXTIME(:start_date)
        AND created_at <= FROM_UNIXTIME(:end_date)
        GROUP BY JSON_EXTRACT(items_sold, '$[*].id')
        ORDER BY total_quantity DESC
        LIMIT :limit;
    -- # }

    -- # { voidTransaction
    -- # :transaction_uuid string
        UPDATE store_transactions 
        SET transaction_type = 'void'
        WHERE transaction_uuid = :transaction_uuid;
    -- # }

-- # }