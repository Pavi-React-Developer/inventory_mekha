-- ========================================================
-- INVENTO: Multi-Concern Inventory Management System Schema
-- ========================================================

CREATE DATABASE IF NOT EXISTS `invento_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `invento_db`;

-- 1. CONCERNS
CREATE TABLE IF NOT EXISTS `concerns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `address` TEXT NULL,
    `gstin` VARCHAR(30) NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(100) NULL,
    `logo` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. MODULES REGISTRY
CREATE TABLE IF NOT EXISTS `modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-grid',
    `sort_order` INT NOT NULL DEFAULT 0,
    `group_name` VARCHAR(50) NOT NULL, -- 'Super Admin', 'Dashboard', 'Purchase', 'Production', 'Stock', 'Sales', 'Finance'
    `is_super_admin_only` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. CONCERN MODULES (Feature toggle per concern)
CREATE TABLE IF NOT EXISTS `concern_modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `module_id` INT NOT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_concern_module` (`concern_id`, `module_id`),
    CONSTRAINT `fk_cm_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cm_module` FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ROLES
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NULL, -- NULL = System Super Admin role
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_roles_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. ROLE PERMISSIONS
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `module_id` INT NOT NULL,
    `can_view` TINYINT(1) NOT NULL DEFAULT 1,
    `can_add` TINYINT(1) NOT NULL DEFAULT 0,
    `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
    `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_role_module` (`role_id`, `module_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_module` FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. USERS
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NULL, -- NULL = Super Admin user
    `role_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. CATEGORIES, SUBCATEGORIES, BRANDS
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cat_concern` (`concern_id`),
    CONSTRAINT `fk_cat_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subcategories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_subcat_concern` (`concern_id`),
    CONSTRAINT `fk_subcat_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_subcat_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `brands` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_brand_concern` (`concern_id`),
    CONSTRAINT `fk_brand_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. VARIANTS (Master product detail row)
CREATE TABLE IF NOT EXISTS `variants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `sku_code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `category_id` INT NOT NULL,
    `subcategory_id` INT NOT NULL,
    `brand_id` INT NULL,
    `size` VARCHAR(50) NULL,
    `weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `weight_unit` VARCHAR(20) NOT NULL DEFAULT 'kg',
    `attributes` JSON NULL, -- color, fabric, gsm, cut, etc.
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `mrp` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `creation_date` DATE NOT NULL,
    `expiry_date` DATE NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_concern_sku` (`concern_id`, `sku_code`),
    INDEX `idx_var_concern` (`concern_id`),
    CONSTRAINT `fk_var_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_var_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
    CONSTRAINT `fk_var_subcat` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`),
    CONSTRAINT `fk_var_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. STOCKS (Live inventory balance)
CREATE TABLE IF NOT EXISTS `stocks` (
    `concern_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `location` VARCHAR(50) NOT NULL DEFAULT 'Main Store',
    `qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`concern_id`, `variant_id`, `location`),
    INDEX `idx_stocks_concern` (`concern_id`),
    CONSTRAINT `fk_stk_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stk_variant` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. STOCK LEDGER (Append-only audit trail for all stock moves)
CREATE TABLE IF NOT EXISTS `stock_ledger` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `location` VARCHAR(50) NOT NULL DEFAULT 'Main Store',
    `qty_in` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `qty_out` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `ref_module` VARCHAR(50) NOT NULL,
    `ref_id` INT NULL,
    `note` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ledger_concern` (`concern_id`),
    INDEX `idx_ledger_variant` (`variant_id`),
    CONSTRAINT `fk_sl_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sl_variant` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. LABEL JOBS (Tracking generated QR labels)
CREATE TABLE IF NOT EXISTS `label_jobs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `qty` INT NOT NULL DEFAULT 1,
    `printed_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_label_concern` (`concern_id`),
    CONSTRAINT `fk_lj_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lj_variant` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. DOC SERIES (Number sequence generation per doc type)
CREATE TABLE IF NOT EXISTS `doc_series` (
    `concern_id` INT NOT NULL,
    `doc_type` VARCHAR(50) NOT NULL, -- 'INVOICE', 'PO', 'BATCH', 'INWARD', 'OUTWARD', 'REQ', 'DAMAGE'
    `prefix` VARCHAR(20) NOT NULL,
    `next_no` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`concern_id`, `doc_type`),
    CONSTRAINT `fk_ds_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. AUDIT LOG (Changes across system)
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NULL,
    `user_id` INT NULL,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(50) NOT NULL,
    `row_id` INT NULL,
    `old_json` JSON NULL,
    `new_json` JSON NULL,
    `ip_address` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. SUPPLIERS
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `code` VARCHAR(50) NULL,
    `name` VARCHAR(150) NOT NULL,
    `company_name` VARCHAR(150) NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(100) NULL,
    `gstin` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `district` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sup_concern` (`concern_id`),
    CONSTRAINT `fk_sup_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. CUSTOMERS
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `code` VARCHAR(50) NULL,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(100) NULL,
    `gstin` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cust_concern` (`concern_id`),
    CONSTRAINT `fk_cust_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. PURCHASE BILLS & ITEMS
CREATE TABLE IF NOT EXISTS `purchase_bills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `bill_no` VARCHAR(50) NOT NULL,
    `supplier_id` INT NOT NULL,
    `bill_date` DATE NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
    `inward_status` ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pb_concern` (`concern_id`),
    CONSTRAINT `fk_pb_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pb_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_bill_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `purchase_bill_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `qty` DECIMAL(12,2) NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `total_price` DECIMAL(12,2) NOT NULL,
    INDEX `idx_pbi_concern` (`concern_id`),
    CONSTRAINT `fk_pbi_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pbi_bill` FOREIGN KEY (`purchase_bill_id`) REFERENCES `purchase_bills`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pbi_variant` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. INWARDS & ITEMS
CREATE TABLE IF NOT EXISTS `inwards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `inward_no` VARCHAR(50) NOT NULL,
    `purchase_bill_id` INT NULL,
    `supplier_id` INT NULL,
    `inward_date` DATE NOT NULL,
    `location` VARCHAR(50) NOT NULL DEFAULT 'Main Store',
    `remarks` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_inw_concern` (`concern_id`),
    CONSTRAINT `fk_inw_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inw_pb` FOREIGN KEY (`purchase_bill_id`) REFERENCES `purchase_bills`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_inw_sup` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `inward_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `inward_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `qty` DECIMAL(12,2) NOT NULL,
    `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    INDEX `idx_ini_concern` (`concern_id`),
    CONSTRAINT `fk_ini_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ini_inward` FOREIGN KEY (`inward_id`) REFERENCES `inwards`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ini_variant` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. PURCHASE DAMAGE
CREATE TABLE IF NOT EXISTS `purchase_damages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `damage_no` VARCHAR(50) NOT NULL,
    `supplier_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `qty` DECIMAL(12,2) NOT NULL,
    `location` VARCHAR(50) NOT NULL DEFAULT 'Main Store',
    `reason` TEXT NULL,
    `action_taken` VARCHAR(100) NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pd_concern` (`concern_id`),
    CONSTRAINT `fk_pd_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pd_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    CONSTRAINT `fk_pd_variant` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. CONCERN DAMAGE (Internal Stock Damage)
CREATE TABLE IF NOT EXISTS `concern_damages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `damage_no` VARCHAR(50) NOT NULL,
    `variant_id` INT NOT NULL,
    `qty` DECIMAL(12,2) NOT NULL,
    `location` VARCHAR(50) NOT NULL DEFAULT 'Main Store',
    `reason` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cd_concern` (`concern_id`),
    CONSTRAINT `fk_cd_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cd_variant` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. PRODUCTION: CATALOG & PROGRAM
CREATE TABLE IF NOT EXISTS `catalogs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `code` VARCHAR(50) NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_catlg_concern` (`concern_id`),
    CONSTRAINT `fk_catlg_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `production_programs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `program_no` VARCHAR(50) NOT NULL,
    `catalog_id` INT NULL,
    `target_variant_id` INT NOT NULL,
    `planned_qty` DECIMAL(12,2) NOT NULL,
    `start_date` DATE NULL,
    `target_date` DATE NULL,
    `status` ENUM('planned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'planned',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_prog_concern` (`concern_id`),
    CONSTRAINT `fk_prog_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prog_cat` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_prog_var` FOREIGN KEY (`target_variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. CUTTING & WASTAGE
CREATE TABLE IF NOT EXISTS `cutting_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `log_no` VARCHAR(50) NOT NULL,
    `program_id` INT NULL,
    `raw_variant_id` INT NOT NULL,
    `consumed_qty` DECIMAL(12,2) NOT NULL,
    `cut_pieces` INT NOT NULL,
    `wastage_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `wastage_unit` VARCHAR(20) NOT NULL DEFAULT 'kg',
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cut_concern` (`concern_id`),
    CONSTRAINT `fk_cut_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cut_prog` FOREIGN KEY (`program_id`) REFERENCES `production_programs`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cut_var` FOREIGN KEY (`raw_variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. STITCHING & WASTAGE
CREATE TABLE IF NOT EXISTS `stitching_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `log_no` VARCHAR(50) NOT NULL,
    `program_id` INT NULL,
    `input_pieces` INT NOT NULL,
    `stitched_pieces` INT NOT NULL,
    `wastage_pieces` INT NOT NULL DEFAULT 0,
    `produced_variant_id` INT NOT NULL,
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_stitch_concern` (`concern_id`),
    CONSTRAINT `fk_stitch_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stitch_prog` FOREIGN KEY (`program_id`) REFERENCES `production_programs`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_stitch_var` FOREIGN KEY (`produced_variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. QC STOCKS
CREATE TABLE IF NOT EXISTS `qc_inspections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `qc_no` VARCHAR(50) NOT NULL,
    `variant_id` INT NOT NULL,
    `inspected_qty` DECIMAL(12,2) NOT NULL,
    `passed_qty` DECIMAL(12,2) NOT NULL,
    `rejected_qty` DECIMAL(12,2) NOT NULL,
    `location` VARCHAR(50) NOT NULL DEFAULT 'Main Store',
    `defect_notes` TEXT NULL,
    `inspector_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qc_concern` (`concern_id`),
    CONSTRAINT `fk_qc_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qc_var` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 24. REQUEST (Customer / Branch Demand)
CREATE TABLE IF NOT EXISTS `customer_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `request_no` VARCHAR(50) NOT NULL,
    `customer_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `requested_qty` DECIMAL(12,2) NOT NULL,
    `expected_date` DATE NULL,
    `status` ENUM('pending', 'approved', 'allocated', 'rejected') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cr_concern` (`concern_id`),
    CONSTRAINT `fk_cr_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cr_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    CONSTRAINT `fk_cr_var` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 25. BATCH
CREATE TABLE IF NOT EXISTS `batches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `batch_no` VARCHAR(50) NOT NULL,
    `variant_id` INT NOT NULL,
    `batch_qty` DECIMAL(12,2) NOT NULL,
    `mfg_date` DATE NULL,
    `exp_date` DATE NULL,
    `status` ENUM('active', 'completed', 'closed') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bt_concern` (`concern_id`),
    CONSTRAINT `fk_bt_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bt_var` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 26. BILLING (Sales Invoices & Items)
CREATE TABLE IF NOT EXISTS `sales_invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `invoice_no` VARCHAR(50) NOT NULL,
    `customer_id` INT NOT NULL,
    `invoice_date` DATE NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('cash', 'bank_transfer', 'upi', 'credit') NOT NULL DEFAULT 'cash',
    `payment_status` ENUM('paid', 'partial', 'unpaid') NOT NULL DEFAULT 'paid',
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_si_concern` (`concern_id`),
    CONSTRAINT `fk_si_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_si_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sales_invoice_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `sales_invoice_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `qty` DECIMAL(12,2) NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `total_price` DECIMAL(12,2) NOT NULL,
    INDEX `idx_sii_concern` (`concern_id`),
    CONSTRAINT `fk_sii_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sii_inv` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sii_var` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 27. OUTWARD & ITEMS (Triggers stock deduction via StockService)
CREATE TABLE IF NOT EXISTS `outwards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `outward_no` VARCHAR(50) NOT NULL,
    `sales_invoice_id` INT NULL,
    `customer_id` INT NOT NULL,
    `outward_date` DATE NOT NULL,
    `location` VARCHAR(50) NOT NULL DEFAULT 'Main Store',
    `status` ENUM('prepared', 'dispatched') NOT NULL DEFAULT 'prepared',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_outw_concern` (`concern_id`),
    CONSTRAINT `fk_outw_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_outw_si` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_outw_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `outward_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `outward_id` INT NOT NULL,
    `variant_id` INT NOT NULL,
    `qty` DECIMAL(12,2) NOT NULL,
    INDEX `idx_owi_concern` (`concern_id`),
    CONSTRAINT `fk_owi_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_owi_outw` FOREIGN KEY (`outward_id`) REFERENCES `outwards`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_owi_var` FOREIGN KEY (`variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 28. DISPATCH
CREATE TABLE IF NOT EXISTS `dispatches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `dispatch_no` VARCHAR(50) NOT NULL,
    `outward_id` INT NOT NULL,
    `transporter_name` VARCHAR(100) NULL,
    `vehicle_no` VARCHAR(50) NULL,
    `tracking_no` VARCHAR(100) NULL,
    `dispatch_date` DATE NOT NULL,
    `status` ENUM('in_transit', 'delivered', 'returned') NOT NULL DEFAULT 'in_transit',
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_disp_concern` (`concern_id`),
    CONSTRAINT `fk_disp_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_disp_outw` FOREIGN KEY (`outward_id`) REFERENCES `outwards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 29. RETURN & EXCHANGE
CREATE TABLE IF NOT EXISTS `returns_exchanges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `return_no` VARCHAR(50) NOT NULL,
    `type` ENUM('return', 'exchange') NOT NULL DEFAULT 'return',
    `customer_id` INT NOT NULL,
    `sales_invoice_id` INT NULL,
    `return_date` DATE NOT NULL,
    `returned_variant_id` INT NOT NULL,
    `returned_qty` DECIMAL(12,2) NOT NULL,
    `exchange_variant_id` INT NULL,
    `exchange_qty` DECIMAL(12,2) NULL,
    `reason` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_re_concern` (`concern_id`),
    CONSTRAINT `fk_re_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_re_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    CONSTRAINT `fk_re_si` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_re_ret_var` FOREIGN KEY (`returned_variant_id`) REFERENCES `variants`(`id`),
    CONSTRAINT `fk_re_exc_var` FOREIGN KEY (`exchange_variant_id`) REFERENCES `variants`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 30. EXPENSES
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `expense_no` VARCHAR(50) NOT NULL,
    `expense_date` DATE NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_mode` VARCHAR(50) NOT NULL DEFAULT 'Cash',
    `account_id` INT NULL,
    `description` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_exp_concern` (`concern_id`),
    CONSTRAINT `fk_exp_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 31. CASH ACCOUNTS & CASH TRANSACTIONS
CREATE TABLE IF NOT EXISTS `cash_accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `account_type` ENUM('cash', 'bank', 'petty_cash') NOT NULL DEFAULT 'cash',
    `account_number` VARCHAR(50) NULL,
    `balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ca_concern` (`concern_id`),
    CONSTRAINT `fk_ca_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cash_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `account_id` INT NOT NULL,
    `txn_date` DATE NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `ref_module` VARCHAR(50) NULL, -- 'Billing', 'Purchase', 'Expense', 'Manual'
    `ref_id` INT NULL,
    `description` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ctx_concern` (`concern_id`),
    CONSTRAINT `fk_ctx_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ctx_acc` FOREIGN KEY (`account_id`) REFERENCES `cash_accounts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 32. ASSETS (Super Admin Asset Module - Company Assets)
CREATE TABLE IF NOT EXISTS `assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NULL, -- NULL = Company Headquarter Asset, or assigned to a concern
    `asset_code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(100) NULL, -- 'Machinery', 'Vehicles', 'IT Equipment', 'Furniture'
    `purchase_date` DATE NULL,
    `purchase_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `current_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `location` VARCHAR(100) NULL,
    `status` ENUM('active', 'maintenance', 'disposed') NOT NULL DEFAULT 'active',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_asset_concern` (`concern_id`),
    CONSTRAINT `fk_asset_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 33. CASHIER SHIFTS (Cashier Shift Register & Float Management)
CREATE TABLE IF NOT EXISTS `cashier_shifts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `cashier_name` VARCHAR(150) NOT NULL,
    `shift_no` VARCHAR(50) NOT NULL,
    `opening_notes` TEXT NULL,
    `starting_float` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `is_float_modified` TINYINT(1) NOT NULL DEFAULT 0,
    `denominations_json` JSON NULL,
    `closing_notes` TEXT NULL,
    `closing_float` DECIMAL(12,2) NULL,
    `closing_denominations_json` JSON NULL,
    `expected_closing_float` DECIMAL(12,2) NULL,
    `variance` DECIMAL(12,2) NULL,
    `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    `opened_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `closed_at` TIMESTAMP NULL,
    INDEX `idx_cs_concern` (`concern_id`),
    INDEX `idx_cs_user` (`user_id`),
    CONSTRAINT `fk_cs_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
