<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\DB;

$sql = "CREATE TABLE IF NOT EXISTS cashier_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concern_id INT NOT NULL,
    user_id INT NOT NULL,
    cashier_name VARCHAR(150) NOT NULL,
    shift_no VARCHAR(50) NOT NULL,
    opening_notes TEXT NULL,
    starting_float DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_float_modified TINYINT(1) NOT NULL DEFAULT 0,
    denominations_json JSON NULL,
    closing_notes TEXT NULL,
    closing_float DECIMAL(12,2) NULL,
    closing_denominations_json JSON NULL,
    expected_closing_float DECIMAL(12,2) NULL,
    variance DECIMAL(12,2) NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    INDEX idx_cs_concern (concern_id),
    INDEX idx_cs_user (user_id),
    CONSTRAINT fk_cs_concern FOREIGN KEY (concern_id) REFERENCES concerns(id) ON DELETE CASCADE,
    CONSTRAINT fk_cs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

DB::query($sql);
echo "cashier_shifts created successfully.\n";
