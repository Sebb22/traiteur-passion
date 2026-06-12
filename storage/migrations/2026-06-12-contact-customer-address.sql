SET @contact_customer_address_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'contact_requests'
      AND c.COLUMN_NAME = 'customer_address'
);

SET @add_contact_customer_address_sql := IF(
    @contact_customer_address_exists = 0,
    'ALTER TABLE contact_requests ADD COLUMN customer_address VARCHAR(255) NULL AFTER location',
    'SELECT 1'
);
PREPARE add_contact_customer_address_stmt FROM @add_contact_customer_address_sql;
EXECUTE add_contact_customer_address_stmt;
DEALLOCATE PREPARE add_contact_customer_address_stmt;

SET @contact_customer_postal_code_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'contact_requests'
      AND c.COLUMN_NAME = 'customer_postal_code'
);

SET @add_contact_customer_postal_code_sql := IF(
    @contact_customer_postal_code_exists = 0,
    'ALTER TABLE contact_requests ADD COLUMN customer_postal_code VARCHAR(20) NULL AFTER customer_address',
    'SELECT 1'
);
PREPARE add_contact_customer_postal_code_stmt FROM @add_contact_customer_postal_code_sql;
EXECUTE add_contact_customer_postal_code_stmt;
DEALLOCATE PREPARE add_contact_customer_postal_code_stmt;

SET @contact_customer_city_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
      AND c.TABLE_NAME = 'contact_requests'
      AND c.COLUMN_NAME = 'customer_city'
);

SET @add_contact_customer_city_sql := IF(
    @contact_customer_city_exists = 0,
    'ALTER TABLE contact_requests ADD COLUMN customer_city VARCHAR(120) NULL AFTER customer_postal_code',
    'SELECT 1'
);
PREPARE add_contact_customer_city_stmt FROM @add_contact_customer_city_sql;
EXECUTE add_contact_customer_city_stmt;
DEALLOCATE PREPARE add_contact_customer_city_stmt;