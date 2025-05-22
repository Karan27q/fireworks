-- Add payment_details table to store bank and UPI information
CREATE TABLE IF NOT EXISTS payment_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_type VARCHAR(50) NOT NULL, -- 'bank', 'upi', etc.
    title VARCHAR(100) NOT NULL,
    description TEXT,
    account_name VARCHAR(100),
    account_number VARCHAR(50),
    ifsc_code VARCHAR(20),
    bank_name VARCHAR(100),
    branch_name VARCHAR(100),
    upi_id VARCHAR(100),
    qr_code_image VARCHAR(255),
    display_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default payment details
INSERT INTO payment_details (payment_type, title, description, account_name, account_number, ifsc_code, bank_name, branch_name, display_order, active) 
VALUES ('bank', 'Bank Transfer', 'Make a direct bank transfer to our account', 'Vamsi Crackers', '1234567890', 'SBIN0012345', 'State Bank of India', 'Main Branch', 1, 1);

INSERT INTO payment_details (payment_type, title, description, upi_id, display_order, active) 
VALUES ('upi', 'UPI Payment', 'Make a quick payment using UPI', 'vamsicrackers@upi', 2, 1);
