-- Migration: AI insight / RSA leave-balance tracking / contracts
-- Run this against your Railway MySQL database if you get 500 errors from:
--   GET /api/users/{id}/analysis
--   GET /api/users/me/leave-balance
--   POST /api/users/{id}/contract
--   POST /api/employee-imports (legacy employee import)
--
-- Safe to re-run: uses IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS employee_contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    contract_type VARCHAR(32) NOT NULL DEFAULT 'permanent',
    annual_leave_days DECIMAL(5,2) NOT NULL DEFAULT 15,
    sick_leave_days DECIMAL(5,2) NOT NULL DEFAULT 30,
    other_leave_days DECIMAL(5,2) NOT NULL DEFAULT 5,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_contracts_user (user_id),
    CONSTRAINT fk_employee_contracts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_leave_balances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    leave_year SMALLINT UNSIGNED NOT NULL,
    leave_type ENUM('annual', 'sick', 'other') NOT NULL,
    allocated_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    imported_used_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_user_year_type (user_id, leave_year, leave_type),
    CONSTRAINT fk_employee_leave_balances_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_imports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    imported_by INT UNSIGNED NOT NULL,
    source_name VARCHAR(120) NOT NULL DEFAULT 'legacy-import',
    rows_imported INT UNSIGNED NOT NULL DEFAULT 0,
    rows_skipped INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_imports_user FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
