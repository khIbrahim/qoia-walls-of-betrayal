-- Store management tables for restaurant POS ecosystem

-- Stores table
-- store.create.stores_table
CREATE TABLE IF NOT EXISTS stores (
    store_id VARCHAR(64) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address TEXT,
    phone VARCHAR(32),
    email VARCHAR(255),
    settings JSON,
    metadata JSON,
    is_active BOOLEAN DEFAULT 1,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    INDEX idx_stores_name (name),
    INDEX idx_stores_active (is_active),
    INDEX idx_stores_created (created_at)
);

-- Store members table
-- store.create.members_table
CREATE TABLE IF NOT EXISTS store_members (
    member_id VARCHAR(64) PRIMARY KEY,
    store_id VARCHAR(64) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(32),
    role ENUM('manager', 'supervisor', 'cashier', 'employee') NOT NULL DEFAULT 'employee',
    status ENUM('active', 'inactive', 'suspended', 'terminated') NOT NULL DEFAULT 'active',
    permissions JSON,
    hourly_wage DECIMAL(10,2) DEFAULT 0.00,
    work_schedule JSON,
    metadata JSON,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    last_login_at INT UNSIGNED NULL,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    INDEX idx_members_store (store_id),
    INDEX idx_members_email (email),
    INDEX idx_members_role (role),
    INDEX idx_members_status (status),
    INDEX idx_members_name (first_name, last_name),
    INDEX idx_members_created (created_at)
);

-- Audit log table
-- store.create.audit_table
CREATE TABLE IF NOT EXISTS store_audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    data JSON,
    user_id VARCHAR(64),
    ip_address VARCHAR(45),
    timestamp INT UNSIGNED NOT NULL,
    INDEX idx_audit_action (action),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_timestamp (timestamp)
);

-- Store performance metrics table (optional for analytics)
-- store.create.metrics_table
CREATE TABLE IF NOT EXISTS store_metrics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id VARCHAR(64) NOT NULL,
    metric VARCHAR(255) NOT NULL,
    value DECIMAL(15,4),
    metadata JSON,
    recorded_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    INDEX idx_metrics_store (store_id),
    INDEX idx_metrics_name (metric),
    INDEX idx_metrics_recorded (recorded_at)
);