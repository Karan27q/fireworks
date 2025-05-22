-- Create payment_details table if it doesn't exist
CREATE TABLE IF NOT EXISTS `payment_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `payment_type` enum('bank','upi','other') NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample payment details
INSERT INTO `payment_details` (`title`, `payment_type`, `description`, `short_description`, `account_name`, `account_number`, `ifsc_code`, `bank_name`, `branch_name`, `display_order`, `active`)
VALUES ('Bank Transfer', 'bank', 'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will be processed once the funds have cleared in our account.', 'Direct bank transfer to our account', 'Vamsi Crackers', '1234567890', 'SBIN0012345', 'State Bank of India', 'Main Branch', 1, 1);

INSERT INTO `payment_details` (`title`, `payment_type`, `description`, `short_description`, `upi_id`, `qr_code_image`, `display_order`, `active`)
VALUES ('UPI Payment', 'upi', 'Pay using any UPI app like Google Pay, PhonePe, Paytm, etc. Scan the QR code or use our UPI ID. Your order will be processed immediately after payment confirmation.', 'Pay using UPI apps like Google Pay, PhonePe, Paytm', 'vamsicrackers@upi', 'upi-qr.png', 2, 1);

-- Add admin_notifications table for payment confirmations
CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add payment fields to orders table
ALTER TABLE `orders` 
ADD COLUMN `payment_transaction_id` varchar(100) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `payment_date` date DEFAULT NULL AFTER `payment_transaction_id`,
ADD COLUMN `payment_screenshot` varchar(255) DEFAULT NULL AFTER `payment_date`,
ADD COLUMN `payment_notes` text DEFAULT NULL AFTER `payment_screenshot`;
