<?php
// Include database connection if not already included
if(!isset($conn)) {
    include '../../includes/db_connect.php';
}

// Get admin information if not already set
if(!isset($admin) && isset($_SESSION['admin_id'])) {
    $adminId = $_SESSION['admin_id'];
    $adminQuery = "SELECT * FROM admins WHERE id = $adminId";
    $adminResult = mysqli_query($conn, $adminQuery);
    if($adminResult && mysqli_num_rows($adminResult) > 0) {
        $admin = mysqli_fetch_assoc($adminResult);
    }
}
?>
<div class="topbar">
    <button class="menu-toggle" id="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="user-info">
        <div class="user-dropdown">
            <div class="dropdown-toggle" id="user-dropdown-toggle">
                <img src="assets/images/admin-avatar.png" alt="Admin">
                <span><?php echo isset($admin['name']) ? $admin['name'] : 'Admin'; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            
            <div class="dropdown-menu" id="user-dropdown-menu">
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
                <a href="change-password.php">
                    <i class="fas fa-lock"></i>
                    Change Password
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>
