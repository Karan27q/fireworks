<?php
function checkRememberToken() {
    global $conn;
    
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            // Get token from database
            $stmt = $conn->prepare("
                SELECT u.id, u.first_name, u.last_name, u.email, u.role 
                FROM remember_tokens rt 
                JOIN users u ON rt.user_id = u.id 
                WHERE rt.token = ? AND rt.expires > NOW() AND u.active = 1
            ");
            
            if ($stmt === false) {
                error_log("Failed to prepare remember token statement: " . $conn->error);
                return false;
            }
            
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Update token expiry
                $update_stmt = $conn->prepare("UPDATE remember_tokens SET expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE token = ?");
                if ($update_stmt !== false) {
                    $update_stmt->bind_param("s", $token);
                    $update_stmt->execute();
                }
                
                return true;
            }
            
            // If token is invalid or expired, remove the cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        } catch (Exception $e) {
            error_log("Error in checkRememberToken: " . $e->getMessage());
        }
    }
    
    return false;
}

function updateSessionActivity() {
    global $conn;
    
    if (isset($_SESSION['user_id'])) {
        try {
            $session_id = session_id();
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            // Update or insert session record
            $stmt = $conn->prepare("
                INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE last_activity = NOW()
            ");
            
            if ($stmt === false) {
                error_log("Failed to prepare session activity statement: " . $conn->error);
                return;
            }
            
            $stmt->bind_param("isss", $_SESSION['user_id'], $session_id, $ip_address, $user_agent);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in updateSessionActivity: " . $e->getMessage());
        }
    }
}

function cleanupExpiredSessions() {
    global $conn;
    
    try {
        // Delete expired remember tokens
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE expires < NOW()");
        if ($stmt !== false) {
            $stmt->execute();
        }
        
        // Delete expired password reset tokens
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE expires < NOW()");
        if ($stmt !== false) {
            $stmt->execute();
        }
        
        // Delete old sessions (older than 30 days)
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        if ($stmt !== false) {
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error in cleanupExpiredSessions: " . $e->getMessage());
    }
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check remember token
checkRememberToken();

// Update session activity
updateSessionActivity();

// Cleanup expired sessions (run this occasionally, not on every request)
if (rand(1, 100) === 1) { // 1% chance to run cleanup
    cleanupExpiredSessions();
} 