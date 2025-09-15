-- Store operations SQL queries

-- Save/Update store
-- store.save.store
INSERT INTO stores (
    store_id, name, description, address, phone, email, 
    settings, metadata, is_active, created_at, updated_at
) VALUES (
    :store_id, :name, :description, :address, :phone, :email,
    :settings, :metadata, :is_active, :created_at, :updated_at
) ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    settings = VALUES(settings),
    metadata = VALUES(metadata),
    is_active = VALUES(is_active),
    updated_at = VALUES(updated_at);

-- Save/Update store member
-- store.save.member
INSERT INTO store_members (
    member_id, store_id, first_name, last_name, email, phone,
    role, status, permissions, hourly_wage, work_schedule,
    metadata, created_at, updated_at, last_login_at
) VALUES (
    :member_id, :store_id, :first_name, :last_name, :email, :phone,
    :role, :status, :permissions, :hourly_wage, :work_schedule,
    :metadata, :created_at, :updated_at, :last_login_at
) ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    email = VALUES(email),
    phone = VALUES(phone),
    role = VALUES(role),
    status = VALUES(status),
    permissions = VALUES(permissions),
    hourly_wage = VALUES(hourly_wage),
    work_schedule = VALUES(work_schedule),
    metadata = VALUES(metadata),
    updated_at = VALUES(updated_at),
    last_login_at = VALUES(last_login_at);

-- Load single store
-- store.load.store
SELECT * FROM stores WHERE store_id = :store_id;

-- Load store members
-- store.load.store_members
SELECT * FROM store_members WHERE store_id = :store_id ORDER BY created_at ASC;

-- Load all stores
-- store.load.all_stores
SELECT * FROM stores ORDER BY created_at DESC;

-- Load active stores only
-- store.load.active_stores
SELECT * FROM stores WHERE is_active = 1 ORDER BY name ASC;

-- Delete store
-- store.delete.store
DELETE FROM stores WHERE store_id = :store_id;

-- Delete store members (cascade when store is deleted)
-- store.delete.store_members
DELETE FROM store_members WHERE store_id = :store_id;

-- Delete specific member
-- store.delete.member
DELETE FROM store_members WHERE member_id = :member_id AND store_id = :store_id;

-- Load member by ID
-- store.load.member
SELECT sm.*, s.name as store_name 
FROM store_members sm 
JOIN stores s ON sm.store_id = s.store_id 
WHERE sm.member_id = :member_id;

-- Load member by email
-- store.load.member_by_email
SELECT sm.*, s.name as store_name 
FROM store_members sm 
JOIN stores s ON sm.store_id = s.store_id 
WHERE sm.email = :email;

-- Search members
-- store.search.members
SELECT sm.*, s.name as store_name 
FROM store_members sm 
JOIN stores s ON sm.store_id = s.store_id 
WHERE 
    (:first_name IS NULL OR sm.first_name LIKE CONCAT('%', :first_name, '%')) AND
    (:last_name IS NULL OR sm.last_name LIKE CONCAT('%', :last_name, '%')) AND
    (:email IS NULL OR sm.email LIKE CONCAT('%', :email, '%')) AND
    (:role IS NULL OR sm.role = :role) AND
    (:status IS NULL OR sm.status = :status) AND
    (:store_id IS NULL OR sm.store_id = :store_id)
ORDER BY sm.created_at DESC
LIMIT :limit OFFSET :offset;

-- Count members for pagination
-- store.count.members
SELECT COUNT(*) as total
FROM store_members sm 
JOIN stores s ON sm.store_id = s.store_id 
WHERE 
    (:first_name IS NULL OR sm.first_name LIKE CONCAT('%', :first_name, '%')) AND
    (:last_name IS NULL OR sm.last_name LIKE CONCAT('%', :last_name, '%')) AND
    (:email IS NULL OR sm.email LIKE CONCAT('%', :email, '%')) AND
    (:role IS NULL OR sm.role = :role) AND
    (:status IS NULL OR sm.status = :status) AND
    (:store_id IS NULL OR sm.store_id = :store_id);

-- Store statistics
-- store.stats.store
SELECT 
    'total_members' as metric,
    COUNT(*) as value,
    UNIX_TIMESTAMP() as timestamp
FROM store_members 
WHERE store_id = :store_id

UNION ALL

SELECT 
    'active_members' as metric,
    COUNT(*) as value,
    UNIX_TIMESTAMP() as timestamp
FROM store_members 
WHERE store_id = :store_id AND status = 'active'

UNION ALL

SELECT 
    CONCAT(role, '_count') as metric,
    COUNT(*) as value,
    UNIX_TIMESTAMP() as timestamp
FROM store_members 
WHERE store_id = :store_id 
GROUP BY role

UNION ALL

SELECT 
    CONCAT(status, '_count') as metric,
    COUNT(*) as value,
    UNIX_TIMESTAMP() as timestamp
FROM store_members 
WHERE store_id = :store_id 
GROUP BY status

UNION ALL

SELECT 
    'avg_hourly_wage' as metric,
    AVG(hourly_wage) as value,
    UNIX_TIMESTAMP() as timestamp
FROM store_members 
WHERE store_id = :store_id AND hourly_wage > 0;

-- Update member login timestamp
-- store.update.member_login
UPDATE store_members 
SET last_login_at = :timestamp, updated_at = :timestamp 
WHERE member_id = :member_id;

-- Save audit log entry
-- store.save.audit
INSERT INTO store_audit_log (action, data, user_id, ip_address, timestamp)
VALUES (:action, :data, :user_id, :ip_address, :timestamp);

-- Load audit log
-- store.load.audit
SELECT * FROM store_audit_log 
ORDER BY timestamp DESC 
LIMIT :limit OFFSET :offset;

-- Load filtered audit log
-- store.load.audit_filtered
SELECT * FROM store_audit_log 
WHERE action = :action 
ORDER BY timestamp DESC 
LIMIT :limit OFFSET :offset;

-- Store metrics operations
-- store.save.metric
INSERT INTO store_metrics (store_id, metric, value, metadata, recorded_at)
VALUES (:store_id, :metric, :value, :metadata, :recorded_at);

-- Load store metrics
-- store.load.metrics
SELECT * FROM store_metrics 
WHERE store_id = :store_id 
  AND (:metric IS NULL OR metric = :metric)
  AND (:from_date IS NULL OR recorded_at >= :from_date)
  AND (:to_date IS NULL OR recorded_at <= :to_date)
ORDER BY recorded_at DESC
LIMIT :limit OFFSET :offset;