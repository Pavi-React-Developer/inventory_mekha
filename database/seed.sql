-- ========================================================
-- INVENTO: Seed Data
-- Complete Clean Master & Initial Reference Data Seed
-- ========================================================
USE `invento_db`;

-- Temporarily disable foreign key checks during batch seed import
SET FOREIGN_KEY_CHECKS = 0;

-- 1. SEED MODULES REGISTRY
INSERT INTO `modules` (`code`, `name`, `icon`, `sort_order`, `group_name`, `is_super_admin_only`) VALUES
-- Super Admin modules
('admin_dashboard', 'Dashboard', 'bi-speedometer2', 1, 'Super Admin', 1),
('admin_concerns', 'Concern Management', 'bi-buildings', 2, 'Super Admin', 1),
('admin_assets', 'Asset Management', 'bi-building-gear', 3, 'Super Admin', 1),
('admin_reports', 'Consolidated Reports', 'bi-bar-chart-line', 4, 'Super Admin', 1),

-- Concern modules (22 items)
('concern_dashboard', 'Concern Dashboard', 'bi-speedometer', 10, 'Dashboard', 0),

('suppliers', 'Supplier', 'bi-truck', 20, 'Purchase', 0),
('purchase_billing', 'Purchase Billing', 'bi-receipt', 21, 'Purchase', 0),
('purchase_stocks', 'Purchase Stocks', 'bi-boxes', 22, 'Purchase', 0),
('purchase_damage', 'Purchase Damage', 'bi-shield-x', 23, 'Purchase', 0),

('catalog_program', 'Catalog & Program', 'bi-journal-bookmark', 30, 'Production', 0),
('catalog_mapping', 'Catalog Mapping', 'bi-diagram-3-fill', 24, 'Purchase', 0),
('cutting_wastage', 'Cutting & Wastage', 'bi-scissors', 31, 'Production', 0),
('stitching_wastage', 'Stitching & Wastage', 'bi-layers', 32, 'Production', 0),
('label_generation', 'Label Generation', 'bi-qr-code', 33, 'Production', 0),
('qc_stocks', 'QC Stocks', 'bi-shield-check', 34, 'Production', 0),

('inward', 'Inward', 'bi-box-arrow-in-down', 40, 'Stock', 0),
('stocks', 'Stocks', 'bi-boxes', 41, 'Stock', 0),
('concern_damage', 'Concern Damage', 'bi-x-octagon', 42, 'Stock', 0),
('return_exchange', 'Return & Exchange', 'bi-arrow-left-right', 43, 'Stock', 0),

('customers', 'Customer Details', 'bi-people', 55, 'Shop', 0),
('request', 'Request', 'bi-file-earmark-text', 51, 'Sales', 0),
('batch', 'Batch', 'bi-collection', 52, 'Sales', 0),
('outward', 'Outward', 'bi-box-arrow-up-right', 54, 'Sales', 0),
('dispatch', 'Dispatch', 'bi-send', 55, 'Sales', 0),

('billing', 'POS Billing', 'bi-calculator', 56, 'Shop', 0),

('expense', 'Expense', 'bi-wallet2', 60, 'Finance', 0),
('cash', 'Cash', 'bi-cash-coin', 61, 'Finance', 0),
('cash_flow', 'Cash & Cash Flow', 'bi-cash-coin', 62, 'Finance', 0),
('staff', 'Staff', 'bi-people-fill', 90, 'Staff', 0)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `icon`=VALUES(`icon`), `group_name`=VALUES(`group_name`);

-- 2. SAMPLE CONCERNS (Concern 1: GRM Garments, Concern 2: Apex Fashion Mills)
INSERT INTO `concerns` (`id`, `code`, `name`, `address`, `gstin`, `phone`, `email`, `status`) VALUES
(1, 'CON-GRM', 'GRM Garments Pvt Ltd', '124 Textile Park, Tirupur, Tamil Nadu - 641602', '33AAACG1234F1Z8', '9842100001', 'info@grmgarments.com', 'active'),
(2, 'CON-APEX', 'Apex Fashion Mills', '45 Industrial Estate, Coimbatore, Tamil Nadu - 641001', '33BBBCG5678F2Z1', '9842100002', 'contact@apexfashion.com', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `code`=VALUES(`code`), `gstin`=VALUES(`gstin`);

-- Enable all concern modules for Concern 1 (GRM) & Concern 2 (Apex)
INSERT IGNORE INTO `concern_modules` (`concern_id`, `module_id`, `enabled`)
SELECT 1, `id`, 1 FROM `modules` WHERE `is_super_admin_only` = 0;

INSERT IGNORE INTO `concern_modules` (`concern_id`, `module_id`, `enabled`)
SELECT 2, `id`, 1 FROM `modules` WHERE `is_super_admin_only` = 0;

-- 3. ROLES (Super Admin role: id = 1, Concern Admin roles: 2 & 3)
INSERT INTO `roles` (`id`, `concern_id`, `name`, `description`) VALUES
(1, NULL, 'Super Admin', 'Full unrestricted platform administrative control'),
(2, 1, 'Concern Administrator', 'Full management access for GRM Garments'),
(3, 2, 'Concern Administrator', 'Full management access for Apex Fashion')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`);

-- Assign full permissions to Concern Admin roles for all concern modules
INSERT IGNORE INTO `role_permissions` (`role_id`, `module_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT 2, `id`, 1, 1, 1, 1 FROM `modules` WHERE `is_super_admin_only` = 0;

INSERT IGNORE INTO `role_permissions` (`role_id`, `module_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT 3, `id`, 1, 1, 1, 1 FROM `modules` WHERE `is_super_admin_only` = 0;

-- 4. USERS (Super Admin and Concern Admins, default password: password123)
-- Hash for 'password123': $2y$10$pTmq6dD5LimCWm07WoVcJegXw.qD5sVVECtNmZLI3Kb5d8bGJNyMW
INSERT INTO `users` (`id`, `concern_id`, `role_id`, `name`, `email`, `password_hash`, `phone`, `status`) VALUES
(1, NULL, 1, 'Super Administrator', 'admin@invento.com', '$2y$10$pTmq6dD5LimCWm07WoVcJegXw.qD5sVVECtNmZLI3Kb5d8bGJNyMW', '9876543210', 'active'),
(2, 1, 2, 'Kavitha Admin (GRM)', 'grm.admin@invento.com', '$2y$10$pTmq6dD5LimCWm07WoVcJegXw.qD5sVVECtNmZLI3Kb5d8bGJNyMW', '9842101111', 'active'),
(3, 2, 3, 'Rajesh Admin (Apex)', 'apex.admin@invento.com', '$2y$10$pTmq6dD5LimCWm07WoVcJegXw.qD5sVVECtNmZLI3Kb5d8bGJNyMW', '9842102222', 'active')
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`), `password_hash`=VALUES(`password_hash`);

-- 5. DOCUMENT SERIES
INSERT INTO `doc_series` (`concern_id`, `doc_type`, `prefix`, `next_no`) VALUES
(1, 'INVOICE', 'GRM-INV-', 101),
(1, 'PO', 'GRM-PO-', 101),
(1, 'BATCH', 'GRM-BAT-', 101),
(1, 'INWARD', 'GRM-INW-', 101),
(1, 'OUTWARD', 'GRM-OUT-', 101),
(1, 'REQ', 'GRM-REQ-', 101),
(1, 'DAMAGE', 'GRM-DMG-', 101),
(2, 'INVOICE', 'APX-INV-', 201),
(2, 'PO', 'APX-PO-', 201),
(2, 'BATCH', 'APX-BAT-', 201),
(2, 'INWARD', 'APX-INW-', 201),
(2, 'OUTWARD', 'APX-OUT-', 201),
(2, 'REQ', 'APX-REQ-', 201),
(2, 'DAMAGE', 'APX-DMG-', 201)
ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`);

-- 6. CASH ACCOUNTS
INSERT INTO `cash_accounts` (`id`, `concern_id`, `name`, `account_type`, `account_number`, `balance`) VALUES
(1, 1, 'Petty Cash Counter', 'petty_cash', NULL, 25000.00),
(2, 1, 'HDFC Operating Current A/c', 'bank', '50200012345678', 350000.00),
(3, 2, 'Apex Cash Counter', 'cash', NULL, 15000.00),
(4, 2, 'SBI Current A/c', 'bank', '30998877665', 180000.00)
ON DUPLICATE KEY UPDATE `balance`=VALUES(`balance`);

-- 7. BRANDS
INSERT INTO `brands` (`id`, `concern_id`, `name`) VALUES
(1, 1, 'Vinand Apparels'),
(2, 1, 'Tirupur Classic'),
(3, 2, 'Apex Premium')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 8. HSN TAX CLASSIFICATION MASTERS
INSERT INTO `hsn_masters` (`id`, `concern_id`, `hsn_code`, `description`, `gst_rate`) VALUES
(1, 1, '5208', 'Woven fabrics of cotton containing 85% or more cotton', 5.00),
(2, 1, '6109', 'T-shirts, singlets and other vests, knitted or crocheted', 5.00),
(3, 1, '6205', 'Men or boys shirts (woven garments)', 5.00),
(4, 1, '9606', 'Buttons, press-fasteners, snap-fasteners and stud buttons', 12.00),
(5, 2, '5205', 'Cotton yarn (other than sewing thread)', 5.00)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `gst_rate`=VALUES(`gst_rate`);

-- 9. CATEGORIES (Tier-1 Main Categories)
INSERT INTO `categories` (`id`, `concern_id`, `name`, `code`, `default_hsn`, `default_gst_rate`, `status`) VALUES
(1, 1, 'Fabrics', 'FAB', '5208', 5.00, 'active'),
(2, 1, 'Apparel & Garments', 'APP', '6109', 5.00, 'active'),
(3, 1, 'Accessories & Trims', 'ACC', '9606', 12.00, 'active'),
(4, 2, 'Yarns & Raw Materials', 'YRN', '5205', 5.00, 'active'),
(5, 2, 'Knitted Apparel', 'KNT', '6109', 5.00, 'active'),
(6, 1, 'Maternity', 'MAT', '6109', 5.00, 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `code`=VALUES(`code`), `default_hsn`=VALUES(`default_hsn`), `default_gst_rate`=VALUES(`default_gst_rate`);

-- 10. SUBCATEGORIES (Tier-2 Product Groups)
INSERT INTO `subcategories` (`id`, `concern_id`, `category_id`, `name`, `code`, `status`) VALUES
(1, 1, 1, 'Knitted Fabric', 'KNF', 'active'),
(2, 1, 1, 'Woven Fabric', 'WVF', 'active'),
(3, 1, 2, 'T-Shirts & Polos', 'TSH', 'active'),
(4, 1, 2, 'Shirts & Tops', 'SHT', 'active'),
(5, 1, 3, 'Buttons & Zippers', 'BTZ', 'active'),
(6, 2, 4, 'Cotton Cone Yarn', 'CCY', 'active'),
(7, 2, 5, 'Sportswear & Activewear', 'SPW', 'active'),
(8, 1, 6, 'Feeding Kurtis & Tops', 'FKT', 'active'),
(9, 1, 6, 'Non-Feeding Loungewear', 'NFL', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `code`=VALUES(`code`);

-- 11. SUB-SUBCATEGORIES (Tier-3 Garment Style Specifications)
INSERT INTO `sub_subcategories` (`id`, `concern_id`, `subcategory_id`, `name`, `code`, `status`) VALUES
(1, 1, 3, 'Crew Neck Classic', 'CNC', 'active'),
(2, 1, 3, 'Polo Collar Premium', 'POL', 'active'),
(3, 1, 4, 'Formal Full Sleeve', 'FFS', 'active'),
(4, 2, 6, 'Combed 30s Cone', 'C30', 'active'),
(5, 1, 8, 'Concealed Zip Kurti', 'CZK', 'active'),
(6, 1, 9, 'Full Length Maxi Dress', 'FLM', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `code`=VALUES(`code`);

-- 12. PRODUCT ATTRIBUTES MASTER (Sizes, Colors, Fabric, Fits)
INSERT INTO `product_attributes` (`id`, `concern_id`, `attribute_type`, `attribute_name`, `attribute_value`, `sort_order`) VALUES
-- Sizes
(1, 1, 'size', 'Size', 'XS', 1),
(2, 1, 'size', 'Size', 'S', 2),
(3, 1, 'size', 'Size', 'M', 3),
(4, 1, 'size', 'Size', 'L', 4),
(5, 1, 'size', 'Size', 'XL', 5),
(6, 1, 'size', 'Size', '2XL', 6),
(7, 1, 'size', 'Size', '3XL', 7),
(8, 1, 'size', 'Size', '4XL', 8),
(9, 1, 'size', 'Size', 'Free Size', 9),
-- Colors
(10, 1, 'color', 'Color', 'Red', 10),
(11, 1, 'color', 'Color', 'Blue', 11),
(12, 1, 'color', 'Color', 'Black', 12),
(13, 1, 'color', 'Color', 'Navy Blue', 13),
(14, 1, 'color', 'Color', 'White', 14),
(15, 1, 'color', 'Color', 'Grey', 15),
(16, 1, 'color', 'Color', 'Maroon', 16),
-- Fabric Types
(17, 1, 'fabric', 'Fabric', 'Combed Cotton', 17),
(18, 1, 'fabric', 'Fabric', 'Single Jersey', 18),
(19, 1, 'fabric', 'Fabric', 'Polyester Spandex', 19),
(20, 1, 'fabric', 'Fabric', 'Linen', 20),
-- Fits
(21, 1, 'fit', 'Fit', 'Regular', 21),
(22, 1, 'fit', 'Fit', 'Slim Fit', 22),
(23, 1, 'fit', 'Fit', 'Oversized', 23)
ON DUPLICATE KEY UPDATE `attribute_name`=VALUES(`attribute_name`), `attribute_value`=VALUES(`attribute_value`), `sort_order`=VALUES(`sort_order`);

-- 13. VARIANTS (Master Product Rows with all category, subcategory & attribute references)
INSERT INTO `variants` (`id`, `concern_id`, `sku_code`, `name`, `category_id`, `subcategory_id`, `brand_id`, `size`, `weight`, `weight_unit`, `attributes`, `price`, `mrp`, `creation_date`, `expiry_date`, `status`) VALUES
(1, 1, 'SKU-FAB-180-BLK', 'Single Jersey 180 GSM Jet Black Fabric', 1, 1, 1, 'Open Width 60"', 100.000, 'kg', '{"color": "Jet Black", "gsm": 180, "fabric_type": "Combed Cotton"}', 280.00, 320.00, '2026-11-01', NULL, 'active'),
(2, 1, 'SKU-TSH-M-NAVY', 'Men Crew Neck Classic T-Shirt Navy M', 2, 3, 1, 'M', 0.220, 'pcs', '{"color": "Navy Blue", "fit": "Regular", "sleeve": "Half"}', 220.00, 499.00, '2026-11-05', NULL, 'active'),
(3, 1, 'SKU-TSH-L-NAVY', 'Men Crew Neck Classic T-Shirt Navy L', 2, 3, 1, 'L', 0.240, 'pcs', '{"color": "Navy Blue", "fit": "Regular", "sleeve": "Half"}', 230.00, 529.00, '2026-11-05', NULL, 'active'),
(4, 1, 'SKU-TSH-XL-RED', 'Men Crew Neck Premium Red XL', 2, 3, 2, 'XL', 0.260, 'pcs', '{"color": "Bright Red", "fit": "Slim", "sleeve": "Half"}', 245.00, 549.00, '2026-11-06', NULL, 'active'),
(5, 2, 'SKU-APX-YRN-30', '30s Combed Cotton Cone Yarn Super White', 4, 6, 3, 'Standard Cone', 500.000, 'kg', '{"count": "30s", "color": "Raw White"}', 310.00, 350.00, '2026-11-01', NULL, 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `price`=VALUES(`price`), `mrp`=VALUES(`mrp`);

-- 14. INITIAL STOCKS
INSERT INTO `stocks` (`concern_id`, `variant_id`, `location`, `qty`) VALUES
(1, 1, 'Main Store', 120.00),
(1, 2, 'Main Store', 350.00),
(1, 3, 'Main Store', 280.00),
(1, 4, 'Main Store', 190.00),
(2, 5, 'Main Store', 750.00)
ON DUPLICATE KEY UPDATE `qty`=VALUES(`qty`);

-- 15. STOCK LEDGER ENTRIES
INSERT INTO `stock_ledger` (`concern_id`, `variant_id`, `location`, `qty_in`, `qty_out`, `ref_module`, `ref_id`, `note`, `created_by`, `created_at`) VALUES
(1, 1, 'Main Store', 120.00, 0.00, 'Inward', 1, 'Initial opening batch from supplier', 2, '2026-11-02 10:00:00'),
(1, 2, 'Main Store', 350.00, 0.00, 'QC Stocks', 1, 'Passed inspection from Stitching Unit', 2, '2026-11-07 11:30:00'),
(1, 3, 'Main Store', 280.00, 0.00, 'QC Stocks', 2, 'Passed inspection batch 102', 2, '2026-11-07 12:00:00'),
(1, 4, 'Main Store', 190.00, 0.00, 'QC Stocks', 3, 'Passed inspection batch 103', 2, '2026-11-08 14:00:00'),
(2, 5, 'Main Store', 750.00, 0.00, 'Inward', 1, 'Opening yarn consignment', 3, '2026-11-03 09:00:00');

-- 16. SUPPLIERS
INSERT INTO `suppliers` (`id`, `concern_id`, `code`, `name`, `company_name`, `phone`, `email`, `gstin`, `address`, `city`, `district`, `state`) VALUES
(1, 1, 'SUP-001', 'Sundaram Spinning Mills', 'Sundaram Textiles Ltd', '9842144556', 'sales@sundaramtextiles.com', '33AAACS1122D1ZP', 'Ring Road', 'Erode', 'Erode', 'Tamil Nadu'),
(2, 1, 'SUP-002', 'ColorTex Dyeing House', 'ColorTex Processors', '9842188990', 'contact@colortex.in', '33AAACT9988C1ZR', 'SIPCOT Industrial Area', 'Perundurai', 'Erode', 'Tamil Nadu')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `phone`=VALUES(`phone`), `gstin`=VALUES(`gstin`);

-- 17. CUSTOMERS
INSERT INTO `customers` (`id`, `concern_id`, `code`, `name`, `phone`, `email`, `gstin`, `address`, `city`) VALUES
(1, 1, 'CUST-001', 'Reliance Retail Trends', '9988776655', 'vendor.trends@reliance.com', '27AAACR7788A1Z5', 'Bandra Kurla Complex, Mumbai', 'Mumbai'),
(2, 1, 'CUST-002', 'Chennai Silks Wholesale', '9876501234', 'procure@thechennaisilks.com', '33AAACT4455B1Z9', 'New Market Street, Chennai', 'Chennai')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `phone`=VALUES(`phone`);

-- 18. SUPER ADMIN & CONCERN ASSETS
INSERT INTO `assets` (`id`, `concern_id`, `asset_code`, `name`, `category`, `purchase_date`, `purchase_cost`, `current_value`, `location`, `status`, `notes`) VALUES
(1, NULL, 'AST-HQ-SRV01', 'Dell PowerEdge R750 Database Server', 'IT Equipment', '2025-06-15', 380000.00, 310000.00, 'HQ Server Room', 'active', 'Primary On-Premise Cluster'),
(2, 1, 'AST-GRM-CUT01', 'Eastman Automatic Fabric CNC Cutting Machine', 'Machinery', '2024-03-20', 1450000.00, 1150000.00, 'Unit 1 Cutting Bay', 'active', 'Under annual maintenance contract'),
(3, 1, 'AST-GRM-JUK05', 'Juki DDL-9000C Direct Drive Stitching Machines (Set of 10)', 'Machinery', '2024-08-10', 420000.00, 360000.00, 'Unit 1 Stitching Line 2', 'active', 'Operational')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `current_value`=VALUES(`current_value`);

-- Re-enable foreign key checks after seed import completes
SET FOREIGN_KEY_CHECKS = 1;
