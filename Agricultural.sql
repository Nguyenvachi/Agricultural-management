-- =========================================================
-- AGRICULTURAL MANAGEMENT SYSTEM - DB V3.1
-- Focus:
-- CRUD + Inventory Flow + Internal Transfer
-- Stack: Laravel + MySQL
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1. SYSTEM LOOKUP TYPES
-- =========================================================

CREATE TABLE `sys_lookup_types` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `sys_lookup_values` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `type_id` INT NOT NULL,

    `code` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(255) NOT NULL,

    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_lookup_type`
        FOREIGN KEY (`type_id`)
        REFERENCES `sys_lookup_types`(`id`),

    UNIQUE KEY `uq_lookup_type_code`
        (`type_id`, `code`)
);

-- =========================================================
-- 2. AGENCIES
-- =========================================================

CREATE TABLE `agencies` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,

    `address` TEXT NULL,
    `phone` VARCHAR(20) NULL,

    `is_active` TINYINT(1) DEFAULT 1,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
);

-- =========================================================
-- 3. USERS
-- =========================================================

CREATE TABLE `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `role_id` INT NOT NULL,
    `agency_id` INT NULL,

    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,

    `full_name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,

    `is_active` TINYINT(1) DEFAULT 1,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT `fk_users_role`
        FOREIGN KEY (`role_id`)
        REFERENCES `sys_lookup_values`(`id`),

    CONSTRAINT `fk_users_agency`
        FOREIGN KEY (`agency_id`)
        REFERENCES `agencies`(`id`)
);

-- =========================================================
-- 4. CATEGORIES
-- =========================================================

CREATE TABLE `categories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,

    `description` TEXT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
);

-- =========================================================
-- 5. ITEMS
-- =========================================================

CREATE TABLE `items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `category_id` INT NOT NULL,

    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,

    `unit` VARCHAR(20) DEFAULT 'kg',

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT `fk_items_category`
        FOREIGN KEY (`category_id`)
        REFERENCES `categories`(`id`)
);

-- =========================================================
-- 6. PRICE LISTS
-- =========================================================

CREATE TABLE `price_lists` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `agency_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `price_type_id` INT NOT NULL,

    `price` DECIMAL(15,2) NOT NULL,

    `effective_from` DATE NOT NULL,
    `effective_to` DATE NULL,

    `is_active` TINYINT(1) DEFAULT 1,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_price_agency`
        FOREIGN KEY (`agency_id`)
        REFERENCES `agencies`(`id`),

    CONSTRAINT `fk_price_item`
        FOREIGN KEY (`item_id`)
        REFERENCES `items`(`id`),

    CONSTRAINT `fk_price_type`
        FOREIGN KEY (`price_type_id`)
        REFERENCES `sys_lookup_values`(`id`),

    UNIQUE KEY `uq_price_effective`
        (`agency_id`, `item_id`, `price_type_id`, `effective_from`),

    INDEX `idx_price_lookup`
        (`agency_id`, `item_id`, `price_type_id`, `is_active`)
);

-- =========================================================
-- 7. INVENTORIES (CURRENT STOCK SNAPSHOT)
-- =========================================================

CREATE TABLE `inventories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `agency_id` INT NOT NULL,
    `item_id` INT NOT NULL,

    `quantity` DECIMAL(15,2) DEFAULT 0,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_inventory_agency`
        FOREIGN KEY (`agency_id`)
        REFERENCES `agencies`(`id`),

    CONSTRAINT `fk_inventory_item`
        FOREIGN KEY (`item_id`)
        REFERENCES `items`(`id`),

    UNIQUE KEY `uq_inventory_agency_item`
        (`agency_id`, `item_id`),

    INDEX `idx_inventory_lookup`
        (`agency_id`, `item_id`)
);

-- =========================================================
-- 8. ORDERS
-- =========================================================

CREATE TABLE `orders` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `order_code` VARCHAR(50) NOT NULL UNIQUE,

    `agency_id` INT NOT NULL,

    -- dùng cho chuyển kho nội bộ
    `to_agency_id` INT NULL,

    -- tham chiếu đơn gốc
    `reference_order_id` INT NULL,

    `user_id` INT NOT NULL,

    `order_type_id` INT NOT NULL,
    `status_id` INT NOT NULL,

    `total_amount` DECIMAL(18,2) DEFAULT 0,

    `note` TEXT NULL,

    `order_date` DATE NOT NULL,

    `created_by` INT NOT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT `fk_orders_agency`
        FOREIGN KEY (`agency_id`)
        REFERENCES `agencies`(`id`),

    CONSTRAINT `fk_orders_to_agency`
        FOREIGN KEY (`to_agency_id`)
        REFERENCES `agencies`(`id`),

    CONSTRAINT `fk_orders_reference`
        FOREIGN KEY (`reference_order_id`)
        REFERENCES `orders`(`id`),

    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`),

    CONSTRAINT `fk_orders_type`
        FOREIGN KEY (`order_type_id`)
        REFERENCES `sys_lookup_values`(`id`),

    CONSTRAINT `fk_orders_status`
        FOREIGN KEY (`status_id`)
        REFERENCES `sys_lookup_values`(`id`),

    CONSTRAINT `fk_orders_creator`
        FOREIGN KEY (`created_by`)
        REFERENCES `users`(`id`),

    INDEX `idx_orders_date_status`
        (`order_date`, `status_id`),

    INDEX `idx_orders_type`
        (`order_type_id`)
);

-- =========================================================
-- 9. ORDER DETAILS
-- =========================================================

CREATE TABLE `order_details` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `order_id` INT NOT NULL,
    `item_id` INT NOT NULL,

    `quantity` DECIMAL(15,2) NOT NULL,
    `unit_price` DECIMAL(15,2) NOT NULL,
    `total_price` DECIMAL(18,2) NOT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_order_details_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders`(`id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_order_details_item`
        FOREIGN KEY (`item_id`)
        REFERENCES `items`(`id`),

    UNIQUE KEY `uq_order_item`
        (`order_id`, `item_id`)
);

-- =========================================================
-- 10. INVENTORY TRANSACTIONS (STOCK CARD)
-- =========================================================

CREATE TABLE `inventory_transactions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,

    `agency_id` INT NOT NULL,
    `item_id` INT NOT NULL,

    `order_id` INT NOT NULL,

    `transaction_type_id` INT NOT NULL,

    `quantity_change` DECIMAL(15,2) NOT NULL,

    `balance_before` DECIMAL(15,2) NOT NULL,
    `balance_after` DECIMAL(15,2) NOT NULL,

    `reference_type` VARCHAR(100) NULL,
    `note` TEXT NULL,

    `created_by` INT NOT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_inventory_transactions_agency`
        FOREIGN KEY (`agency_id`)
        REFERENCES `agencies`(`id`),

    CONSTRAINT `fk_inventory_transactions_item`
        FOREIGN KEY (`item_id`)
        REFERENCES `items`(`id`),

    CONSTRAINT `fk_inventory_transactions_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders`(`id`),

    CONSTRAINT `fk_inventory_transactions_type`
        FOREIGN KEY (`transaction_type_id`)
        REFERENCES `sys_lookup_values`(`id`),

    CONSTRAINT `fk_inventory_transactions_creator`
        FOREIGN KEY (`created_by`)
        REFERENCES `users`(`id`),

    INDEX `idx_inventory_transactions_lookup`
        (`agency_id`, `item_id`, `created_at`)
);

-- =========================================================
-- LOOKUP DATA
-- =========================================================

INSERT INTO `sys_lookup_types`
(`id`, `code`, `name`)
VALUES
(1, 'USER_ROLE', 'Vai trò người dùng'),
(2, 'PRICE_TYPE', 'Loại bảng giá'),
(3, 'ORDER_TYPE', 'Loại đơn hàng'),
(4, 'ORDER_STATUS', 'Trạng thái đơn hàng'),
(5, 'TRANSACTION_TYPE', 'Loại biến động kho');

INSERT INTO `sys_lookup_values`
(`type_id`, `code`, `display_name`)
VALUES

-- USER ROLE
(1, 'ADMIN', 'Quản trị viên'),
(1, 'AGENCY', 'Đại lý'),
(1, 'FARMER', 'Nông hộ'),
(1, 'CUSTOMER', 'Khách hàng'),

-- PRICE TYPE
(2, 'BUY', 'Giá thu mua'),
(2, 'SELL', 'Giá bán'),

-- ORDER TYPE
(3, 'PURCHASE_ORDER', 'Đơn nhập hàng'),
(3, 'SALES_ORDER', 'Đơn bán hàng'),
(3, 'INTERNAL_TRANSFER', 'Chuyển kho nội bộ'),
(3, 'RETURN_ORDER', 'Đơn trả hàng'),
(3, 'ADJUSTMENT_ORDER', 'Điều chỉnh kho'),

-- ORDER STATUS
(4, 'PENDING', 'Chờ xử lý'),
(4, 'PROCESSING', 'Đang xử lý'),
(4, 'COMPLETED', 'Hoàn thành'),
(4, 'CANCELLED', 'Đã hủy'),

-- TRANSACTION TYPE
(5, 'IMPORT', 'Nhập kho'),
(5, 'EXPORT', 'Xuất kho');

SET FOREIGN_KEY_CHECKS = 1;