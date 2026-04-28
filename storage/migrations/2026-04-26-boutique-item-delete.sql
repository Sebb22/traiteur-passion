CREATE TABLE IF NOT EXISTS boutique_item_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_cents INT NOT NULL DEFAULT 0,
    price_label VARCHAR(80) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES boutique_items(id) ON DELETE CASCADE,
    INDEX idx_boutique_item_options_item (item_id),
    INDEX idx_boutique_item_options_order (item_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @order_item_fk_name := (
    SELECT kcu.CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE kcu
    WHERE kcu.TABLE_SCHEMA = DATABASE()
      AND kcu.TABLE_NAME = 'boutique_order_items'
      AND kcu.COLUMN_NAME = 'item_id'
      AND kcu.REFERENCED_TABLE_NAME = 'boutique_items'
    LIMIT 1
);

SET @drop_order_item_fk_sql := IF(
    @order_item_fk_name IS NULL,
    'SELECT 1',
    CONCAT('ALTER TABLE boutique_order_items DROP FOREIGN KEY `', @order_item_fk_name, '`')
);
PREPARE drop_order_item_fk_stmt FROM @drop_order_item_fk_sql;
EXECUTE drop_order_item_fk_stmt;
DEALLOCATE PREPARE drop_order_item_fk_stmt;

ALTER TABLE boutique_order_items
    MODIFY COLUMN item_id INT NULL;

ALTER TABLE boutique_order_items
    ADD CONSTRAINT fk_boutique_order_items_item
        FOREIGN KEY (item_id) REFERENCES boutique_items(id) ON DELETE SET NULL;