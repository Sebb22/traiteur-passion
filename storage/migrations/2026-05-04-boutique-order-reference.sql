SET @order_reference_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'boutique_orders'
      AND c.COLUMN_NAME = 'order_reference'
);

SET @add_order_reference_column_sql := IF(
    @order_reference_column_exists = 0,
    'ALTER TABLE boutique_orders ADD COLUMN order_reference VARCHAR(32) NULL AFTER id',
    'SELECT 1'
);
PREPARE add_order_reference_column_stmt FROM @add_order_reference_column_sql;
EXECUTE add_order_reference_column_stmt;
DEALLOCATE PREPARE add_order_reference_column_stmt;

UPDATE boutique_orders
SET order_reference = CONCAT(
    'TPB-',
    DATE_FORMAT(COALESCE(created_at, NOW()), '%Y%m%d'),
    '-',
    UPPER(SUBSTRING(REPLACE(UUID(), '-', ''), 1, 8))
)
WHERE order_reference IS NULL OR TRIM(order_reference) = '';

SET @order_reference_is_required := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'boutique_orders'
      AND c.COLUMN_NAME = 'order_reference'
      AND c.IS_NULLABLE = 'NO'
);

SET @set_order_reference_required_sql := IF(
    @order_reference_is_required = 0,
    'ALTER TABLE boutique_orders MODIFY COLUMN order_reference VARCHAR(32) NOT NULL',
    'SELECT 1'
);
PREPARE set_order_reference_required_stmt FROM @set_order_reference_required_sql;
EXECUTE set_order_reference_required_stmt;
DEALLOCATE PREPARE set_order_reference_required_stmt;

SET @order_reference_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS s
    WHERE s.TABLE_SCHEMA = DATABASE()
      AND s.TABLE_NAME = 'boutique_orders'
      AND s.INDEX_NAME = 'uq_boutique_orders_reference'
      AND s.NON_UNIQUE = 0
);

SET @add_order_reference_unique_sql := IF(
    @order_reference_unique_exists = 0,
    'ALTER TABLE boutique_orders ADD UNIQUE INDEX uq_boutique_orders_reference (order_reference)',
    'SELECT 1'
);
PREPARE add_order_reference_unique_stmt FROM @add_order_reference_unique_sql;
EXECUTE add_order_reference_unique_stmt;
DEALLOCATE PREPARE add_order_reference_unique_stmt;