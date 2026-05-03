SET @option_stock_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'boutique_item_options'
      AND c.COLUMN_NAME = 'stock_quantity'
);

SET @add_option_stock_column_sql := IF(
    @option_stock_column_exists = 0,
    'ALTER TABLE boutique_item_options ADD COLUMN stock_quantity INT NULL DEFAULT NULL AFTER quantity',
    'SELECT 1'
);
PREPARE add_option_stock_column_stmt FROM @add_option_stock_column_sql;
EXECUTE add_option_stock_column_stmt;
DEALLOCATE PREPARE add_option_stock_column_stmt;

SET @order_option_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'boutique_order_items'
      AND c.COLUMN_NAME = 'option_id'
);

SET @add_order_option_column_sql := IF(
    @order_option_column_exists = 0,
    'ALTER TABLE boutique_order_items ADD COLUMN option_id INT NULL AFTER item_id',
    'SELECT 1'
);
PREPARE add_order_option_column_stmt FROM @add_order_option_column_sql;
EXECUTE add_order_option_column_stmt;
DEALLOCATE PREPARE add_order_option_column_stmt;

SET @order_option_fk_name := (
    SELECT kcu.CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE kcu
    WHERE kcu.TABLE_SCHEMA = DATABASE()
      AND kcu.TABLE_NAME = 'boutique_order_items'
      AND kcu.COLUMN_NAME = 'option_id'
      AND kcu.REFERENCED_TABLE_NAME = 'boutique_item_options'
    LIMIT 1
);

SET @add_order_option_fk_sql := IF(
    @order_option_fk_name IS NULL,
    'ALTER TABLE boutique_order_items ADD CONSTRAINT fk_boutique_order_items_option FOREIGN KEY (option_id) REFERENCES boutique_item_options(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE add_order_option_fk_stmt FROM @add_order_option_fk_sql;
EXECUTE add_order_option_fk_stmt;
DEALLOCATE PREPARE add_order_option_fk_stmt;

SET @order_option_idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS s
    WHERE s.TABLE_SCHEMA = DATABASE()
      AND s.TABLE_NAME = 'boutique_order_items'
      AND s.INDEX_NAME = 'idx_boutique_order_items_option'
);

SET @add_order_option_idx_sql := IF(
    @order_option_idx_exists = 0,
    'ALTER TABLE boutique_order_items ADD INDEX idx_boutique_order_items_option (option_id)',
    'SELECT 1'
);
PREPARE add_order_option_idx_stmt FROM @add_order_option_idx_sql;
EXECUTE add_order_option_idx_stmt;
DEALLOCATE PREPARE add_order_option_idx_stmt;