-- ====================================================
-- Database schema for Traiteur Passion
-- Contact requests with menu items
-- ====================================================

-- Create database (if needed)
CREATE DATABASE IF NOT EXISTS traiteur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE traiteur;

-- ====================================================
-- Create application user and grant privileges
-- ====================================================
CREATE USER IF NOT EXISTS 'tp'@'localhost' IDENTIFIED BY 'tpAdmin@@';
GRANT ALL PRIVILEGES ON traiteur.* TO 'tp'@'localhost';
FLUSH PRIVILEGES;

-- ====================================================
-- Table: contact_requests
-- Store all contact form submissions
-- ====================================================
CREATE TABLE IF NOT EXISTS contact_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    people INT NULL,
    date DATE NULL,
    location VARCHAR(255) NULL,
    type VARCHAR(100) NULL COMMENT 'Type d''événement: mariage, entreprise, cocktail, etc.',
    message TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'quoted', 'completed', 'cancelled') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- Table: contact_menu_items
-- Store selected menu items for each contact request
-- ====================================================
CREATE TABLE IF NOT EXISTS contact_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    menu_item_name VARCHAR(255) NOT NULL,
    menu_item_category VARCHAR(100) NOT NULL COMMENT 'paniers, carte, aperitif, brunch, buffet, poelon',
    menu_item_price VARCHAR(50) NULL,
    quantity INT DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (contact_id) REFERENCES contact_requests(id) ON DELETE CASCADE,
    INDEX idx_contact_id (contact_id),
    INDEX idx_category (menu_item_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- Tables: menu_*
-- Dynamic menu catalog (sections, items, options)
-- ====================================================

CREATE TABLE IF NOT EXISTS menu_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE COMMENT 'paniers, a-la-carte, aperitif-animation, brunch, buffet-froid, plateaux-repas, plat-unique',
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_menu_sections_order (sort_order),
    INDEX idx_menu_sections_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    slug VARCHAR(120) NOT NULL,
    name VARCHAR(160) NOT NULL,
    short_description TEXT NULL,
    image_path VARCHAR(255) NULL,
    image_alt VARCHAR(255) NULL,
    price_from_label VARCHAR(80) NULL COMMENT 'Ex: Dès 21,50€ / À partir de 9,70€',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (section_id) REFERENCES menu_sections(id) ON DELETE CASCADE,
    UNIQUE KEY uq_menu_item_slug_in_section (section_id, slug),
    INDEX idx_menu_items_order (section_id, sort_order),
    INDEX idx_menu_items_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_item_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    option_key VARCHAR(100) NOT NULL COMMENT 'Ex: classique-viande, cocktail-7, plancha-crevettes',
    label VARCHAR(180) NOT NULL,
    description VARCHAR(255) NULL,
    price_cents INT NULL COMMENT 'Prix numérique pour futurs calculs / estimations',
    price_label VARCHAR(80) NULL COMMENT 'Ex: 21,50€ / Sur devis / 16,5€/kg',
    is_quote_only TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    UNIQUE KEY uq_menu_option_key_per_item (item_id, option_key),
    INDEX idx_menu_item_options_order (item_id, sort_order),
    INDEX idx_menu_item_options_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- Tables: boutique_*
-- Online shop catalog, stock and orders
-- ====================================================

CREATE TABLE IF NOT EXISTS boutique_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_boutique_sections_order (sort_order),
    INDEX idx_boutique_sections_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boutique_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    slug VARCHAR(120) NOT NULL,
    name VARCHAR(160) NOT NULL,
    short_description TEXT NULL,
    image_path VARCHAR(255) NULL,
    image_alt VARCHAR(255) NULL,
    price_cents INT NOT NULL DEFAULT 0,
    price_label VARCHAR(80) NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 5,
    max_order_quantity INT NOT NULL DEFAULT 10,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (section_id) REFERENCES boutique_sections(id) ON DELETE CASCADE,
    UNIQUE KEY uq_boutique_item_slug_in_section (section_id, slug),
    INDEX idx_boutique_items_order (section_id, sort_order),
    INDEX idx_boutique_items_active (is_active),
    INDEX idx_boutique_items_stock (stock_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boutique_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(50) NULL,
    fulfillment_method ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
    pickup_date DATE NOT NULL,
    pickup_slot VARCHAR(120) NULL,
    delivery_address VARCHAR(255) NULL,
    delivery_postal_code VARCHAR(20) NULL,
    delivery_city VARCHAR(120) NULL,
    message TEXT NULL,
    promo_code VARCHAR(40) NULL,
    promo_label VARCHAR(120) NULL,
    discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    discount_cents INT NOT NULL DEFAULT 0,
    status ENUM('new', 'confirmed', 'preparing', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
    stock_restored_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_boutique_orders_status (status),
    INDEX idx_boutique_orders_created (created_at),
    INDEX idx_boutique_orders_pickup (pickup_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boutique_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_name_snapshot VARCHAR(255) NOT NULL,
    section_name_snapshot VARCHAR(120) NOT NULL,
    unit_price_cents INT NOT NULL DEFAULT 0,
    unit_price_label VARCHAR(80) NULL,
    quantity INT NOT NULL DEFAULT 1,
    line_total_cents INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id) REFERENCES boutique_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES boutique_items(id) ON DELETE RESTRICT,
    INDEX idx_boutique_order_items_order (order_id),
    INDEX idx_boutique_order_items_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- Tables: blog_*
-- Mini CMS blog content managed from admin
-- ====================================================

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    date_iso DATE NOT NULL,
    author VARCHAR(160) NOT NULL,
    excerpt TEXT NULL,
    categories_json TEXT NULL,
    cover_image VARCHAR(255) NULL,
    video_url VARCHAR(255) NULL,
    intro TEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_blog_posts_date (date_iso),
    INDEX idx_blog_posts_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    title VARCHAR(255) NULL,
    body TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    INDEX idx_blog_post_sections_order (post_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- Sample data (optional - for testing)
-- ====================================================
-- INSERT INTO contact_requests (name, email, phone, people, date, location, type, message, status)
-- VALUES 
-- ('Jean Dupont', 'jean@example.com', '+33612345678', 50, '2026-06-15', 'Salle des Fêtes, Vignemont', 'mariage', 'Besoin d''un devis pour un mariage avec buffet froid', 'new'),
-- ('Marie Martin', 'marie@example.com', '+33687654321', 30, '2026-05-20', 'Entreprise ABC, Paris', 'entreprise', 'Cocktail pour séminaire d''entreprise', 'new');
