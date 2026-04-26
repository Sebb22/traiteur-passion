ALTER TABLE boutique_items
    ADD COLUMN stock_unit VARCHAR(16) NOT NULL DEFAULT 'unit' AFTER stock_quantity;