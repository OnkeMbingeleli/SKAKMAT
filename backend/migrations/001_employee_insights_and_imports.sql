CREATE TABLE IF NOT EXISTS employee_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    contract_type VARCHAR(80) NOT NULL DEFAULT 'permanent',
    annual_leave_days DECIMAL(5,2) NOT NULL DEFAULT 15,
    sick_leave_days DECIMAL(5,2) NOT NULL DEFAULT 30,
    other_leave_days DECIMAL(5,2) NOT NULL DEFAULT 5,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contract_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_contract_user_dates (user_id, start_date, end_date)
);

CREATE TABLE IF NOT EXISTS employee_leave_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    leave_year YEAR NOT NULL,
    leave_type VARCHAR(40) NOT NULL,
    allocated_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    imported_used_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_leave_balance (user_id, leave_year, leave_type),
    CONSTRAINT fk_balance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employee_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imported_by INT UNSIGNED NOT NULL,
    source_name VARCHAR(160) NOT NULL,
    rows_imported INT NOT NULL DEFAULT 0,
    rows_skipped INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_user FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE RESTRICT
);
