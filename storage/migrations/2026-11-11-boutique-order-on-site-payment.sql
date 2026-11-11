SET @payment_status_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'boutique_orders'
      AND c.COLUMN_NAME = 'payment_status'
);

SET @add_payment_status_column_sql := IF(
    @payment_status_column_exists = 0,
    "ALTER TABLE boutique_orders ADD COLUMN payment_status ENUM('unpaid', 'paid_on_site') NOT NULL DEFAULT 'unpaid' AFTER status",
    'SELECT 1'
);
PREPARE add_payment_status_column_stmt FROM @add_payment_status_column_sql;
EXECUTE add_payment_status_column_stmt;
DEALLOCATE PREPARE add_payment_status_column_stmt;

SET @payment_status_updated_at_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'boutique_orders'
      AND c.COLUMN_NAME = 'payment_status_updated_at'
);

SET @add_payment_status_updated_at_sql := IF(
    @payment_status_updated_at_exists = 0,
    "ALTER TABLE boutique_orders ADD COLUMN payment_status_updated_at TIMESTAMP NULL DEFAULT NULL AFTER payment_status",
    'SELECT 1'
);
PREPARE add_payment_status_updated_at_stmt FROM @add_payment_status_updated_at_sql;
EXECUTE add_payment_status_updated_at_stmt;
DEALLOCATE PREPARE add_payment_status_updated_at_stmt;

SET @payment_status_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS s
    WHERE s.TABLE_SCHEMA = DATABASE()
      AND s.TABLE_NAME = 'boutique_orders'
      AND s.INDEX_NAME = 'idx_boutique_orders_payment_status'
);

SET @add_payment_status_index_sql := IF(
    @payment_status_index_exists = 0,
    'ALTER TABLE boutique_orders ADD INDEX idx_boutique_orders_payment_status (payment_status)',
    'SELECT 1'
);
PREPARE add_payment_status_index_stmt FROM @add_payment_status_index_sql;
EXECUTE add_payment_status_index_stmt;
DEALLOCATE PREPARE add_payment_status_index_stmt;