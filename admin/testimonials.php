<?php
session_start();

// Define admin path constant
define('ADMIN_PATH', true);

// Include database connection
include '../includes/db_connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get admin information
$adminId = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admins WHERE id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

// Handle testimonial status updates
if(isset($_POST['update_status'])) {
    $testimonial_id = mysqli_real_escape_string($conn, $_POST['testimonial_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $updateQuery = "UPDATE testimonials SET status = '$new_status' WHERE id = $testimonial_id";
    if(mysqli_query($conn, $updateQuery)) {
        $success = "Testimonial status updated successfully";
    } else {
        $error = "Error updating testimonial status";
    }
}

// Handle testimonial deletion
if(isset($_GET['delete'])) {
    $testimonial_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    $deleteQuery = "DELETE FROM testimonials WHERE id = $testimonial_id";
    if(mysqli_query($conn, $deleteQuery)) {
        $success = "Testimonial deleted successfully";
    } else {
        $error = "Error deleting testimonial";
    }
}

// Get all testimonials
$query = "SELECT * FROM testimonials ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$testimonials = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .testimonial-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .testimonial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .testimonial-content {
            margin-bottom: 15px;
        }
        
        .testimonial-meta {
            color: #666;
            font-size: 0.9em;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Content -->
            <div class="content">
                <div class="content-header">
                    <h1>Manage Testimonials</h1>
                </div>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="testimonials-grid">
                    <?php foreach($testimonials as $testimonial): ?>
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <h3><?php echo htmlspecialchars($testimonial['name']); ?></h3>
                                <span class="status-badge status-<?php echo $testimonial['status']; ?>">
                                    <?php echo ucfirst($testimonial['status']); ?>
                                </span>
                            </div>
                            
                            <div class="testimonial-content">
                                <p><?php echo nl2br(htmlspecialchars($testimonial['content'])); ?></p>
                            </div>
                            
                            <div class="testimonial-meta">
                                <p>Rating: <?php echo $testimonial['rating']; ?>/5</p>
                                <p>Date: <?php echo date('M d, Y', strtotime($testimonial['created_at'])); ?></p>
                            </div>
                            
                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $testimonial['status'] == 'approved' ? 'pending' : 'approved'; ?>">
                                    <button type="submit" name="update_status" class="btn btn-sm <?php echo $testimonial['status'] == 'approved' ? 'btn-warning' : 'btn-success'; ?>">
                                        <?php echo $testimonial['status'] == 'approved' ? 'Unapprove' : 'Approve'; ?>
                                    </button>
                                </form>
                                
                                <a href="?delete=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this testimonial?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // Add any JavaScript functionality here
    </script>
</body>
</html> 