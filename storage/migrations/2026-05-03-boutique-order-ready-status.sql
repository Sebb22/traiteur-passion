SET @order_status_is_ready_enabled := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'boutique_orders'
      AND c.COLUMN_NAME = 'status'
      AND c.COLUMN_TYPE = "enum('new','confirmed','preparing','ready','completed','cancelled')"
);

SET @alter_order_status_sql := IF(
    @order_status_is_ready_enabled = 0,
    "ALTER TABLE boutique_orders MODIFY COLUMN status ENUM('new', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'new'",
    'SELECT 1'
);

PREPARE alter_order_status_stmt FROM @alter_order_status_sql;
EXECUTE alter_order_status_stmt;
DEALLOCATE PREPARE alter_order_status_stmt;