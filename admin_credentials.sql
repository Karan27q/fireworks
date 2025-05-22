-- Admin Panel Login Credentials
-- Email: admin@fireworks.com
-- Password: Admin@123

-- The password is stored as a bcrypt hash in the database
-- You can use these credentials to log into the admin panel

-- If you need to create a new admin user, you can use the following SQL:
INSERT INTO admins (name, email, password, role, created_at) 
VALUES ('Admin User', 'admin@fireworks.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NOW());

-- Note: The password hash above is for 'Admin@123'
