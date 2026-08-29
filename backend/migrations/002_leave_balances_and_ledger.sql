-- Leave types and auditable per-employee leave balances.
CREATE TABLE IF NOT EXISTS leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    default_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    is_paid TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO leave_types (code, name, default_days) VALUES
    ('annual', 'Annual Leave', 15),
    ('sick', 'Sick Leave', 30),
    ('unpaid', 'Unpaid Leave', 0),
    ('family responsibility', 'Family Responsibility Leave', 3),
    ('study leave', 'Study Leave', 5),
    ('maternity leave', 'Maternity Leave', 120),
    ('paternity leave', 'Paternity Leave', 10);

-- 001 created imported_used_days. Keep it as an opening balance when upgrading.
ALTER TABLE employee_leave_balances
    ADD COLUMN used_days DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER allocated_days;

UPDATE employee_leave_balances
SET used_days = imported_used_days
WHERE used_days = 0 AND imported_used_days <> 0;

ALTER TABLE employee_leave_balances
    ADD COLUMN remaining_days DECIMAL(5,2)
        AS (allocated_days - used_days) STORED AFTER used_days,
    ADD COLUMN last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP AFTER remaining_days;

CREATE TABLE IF NOT EXISTS leave_balance_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    leave_type VARCHAR(40) NOT NULL,
    leave_year YEAR NOT NULL,
    request_id INT NULL,
    change_days DECIMAL(5,2) NOT NULL,
    previous_balance DECIMAL(5,2) NOT NULL,
    new_balance DECIMAL(5,2) NOT NULL,
    action_type ENUM('approval', 'rejection', 'cancellation', 'adjustment') NOT NULL,
    performed_by INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ledger_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ledger_request FOREIGN KEY (request_id) REFERENCES leave_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_ledger_performer FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_ledger_user_year (user_id, leave_year),
    INDEX idx_ledger_request (request_id)
);
