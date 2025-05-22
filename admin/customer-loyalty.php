<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include '../includes/db_connect.php';

// Get admin information
$adminId = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admins WHERE id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

// Handle customer group creation
if(isset($_POST['create_group'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $discountPercentage = (float)$_POST['discount_percentage'];
    $minOrderCount = (int)$_POST['min_order_count'];
    $minTotalSpent = (float)$_POST['min_total_spent'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $insertQuery = "INSERT INTO customer_groups (name, discount_percentage, min_order_count, min_total_spent, description, created_at) 
                   VALUES ('$name', $discountPercentage, $minOrderCount, $minTotalSpent, '$description', NOW())";
    
    $insertResult = mysqli_query($conn, $insertQuery);
    
    if($insertResult) {
        $successMessage = "Customer group created successfully";
    } else {
        $errorMessage = "Failed to create customer group: " . mysqli_error($conn);
    }
}

// Handle customer group deletion
if(isset($_GET['delete_group']) && is_numeric($_GET['delete_group'])) {
    $groupId = (int)$_GET['delete_group'];
    
    // Check if group has customers
    $checkQuery = "SELECT COUNT(*) as count FROM users WHERE customer_group_id = $groupId";
    $checkResult = mysqli_query($conn, $checkQuery);
    $customerCount = mysqli_fetch_assoc($checkResult)['count'];
    
    if($customerCount > 0) {
        $errorMessage = "Cannot delete group. $customerCount customers are assigned to this group.";
    } else {
        $deleteQuery = "DELETE FROM customer_groups WHERE id = $groupId";
        $deleteResult = mysqli_query($conn, $deleteQuery);
        
        if($deleteResult) {
            $successMessage = "Customer group deleted successfully";
        } else {
            $errorMessage = "Failed to delete customer group: " . mysqli_error($conn);
        }
    }
}

// Handle customer group update
if(isset($_POST['update_group'])) {
    $groupId = (int)$_POST['group_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $discountPercentage = (float)$_POST['discount_percentage'];
    $minOrderCount = (int)$_POST['min_order_count'];
    $minTotalSpent = (float)$_POST['min_total_spent'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $updateQuery = "UPDATE customer_groups SET 
                   name = '$name',
                   discount_percentage = $discountPercentage,
                   min_order_count = $minOrderCount,
                   min_total_spent = $minTotalSpent,
                   description = '$description',
                   updated_at = NOW()
                   WHERE id = $groupId";
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        $successMessage = "Customer group updated successfully";
    } else {
        $errorMessage = "Failed to update customer group: " . mysqli_error($conn);
    }
}

// Handle manual group assignment
if(isset($_POST['assign_group'])) {
    $userId = (int)$_POST['user_id'];
    $groupId = (int)$_POST['group_id'];
    
    $updateQuery = "UPDATE users SET customer_group_id = $groupId WHERE id = $userId";
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        $successMessage = "Customer group assigned successfully";
    } else {
        $errorMessage = "Failed to assign customer group: " . mysqli_error($conn);
    }
}

// Handle automatic group assignment
if(isset($_POST['auto_assign'])) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get all customer groups
        $groupsQuery = "SELECT * FROM customer_groups ORDER BY min_total_spent DESC, min_order_count DESC";
        $groupsResult = mysqli_query($conn, $groupsQuery);
        $groups = mysqli_fetch_all($groupsResult, MYSQLI_ASSOC);
        
        // Get all customers with their order stats
        $customersQuery = "SELECT 
                          u.id,
                          u.customer_group_id,
                          COUNT(o.id) as order_count,
                          SUM(o.total_amount) as total_spent
                        FROM users u
                        LEFT JOIN orders o ON u.id = o.user_id
                        GROUP BY u.id";
        $customersResult = mysqli_query($conn, $customersQuery);
        $customers = mysqli_fetch_all($customersResult, MYSQLI_ASSOC);
        
        $assignedCount = 0;
        
        foreach($customers as $customer) {
            $orderCount = $customer['order_count'] ?: 0;
            $totalSpent = $customer['total_spent'] ?: 0;
            $currentGroupId = $customer['customer_group_id'];
            $newGroupId = null;
            
            // Find the highest tier group the customer qualifies for
            foreach($groups as $group) {
                if($orderCount >= $group['min_order_count'] && $totalSpent >= $group['min_total_spent']) {
                    $newGroupId = $group['id'];
                    break;
                }
            }
            
            // Update customer's group if it changed
            if($newGroupId !== null && $newGroupId != $currentGroupId) {
                $updateQuery = "UPDATE users SET customer_group_id = $newGroupId WHERE id = {$customer['id']}";
                $updateResult = mysqli_query($conn, $updateQuery);
                
                if(!$updateResult) {
                    throw new Exception("Failed to update customer ID {$customer['id']}: " . mysqli_error($conn));
                }
                
                $assignedCount++;
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $successMessage = "$assignedCount customers assigned to groups based on their order history";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $errorMessage = $e->getMessage();
    }
}

// Get customer groups
$groupsQuery = "SELECT cg.*, COUNT(u.id) as customer_count 
               FROM customer_groups cg
               LEFT JOIN users u ON cg.id = u.customer_group_id
               GROUP BY cg.id
               ORDER BY cg.min_total_spent DESC, cg.min_order_count DESC";
$groupsResult = mysqli_query($conn, $groupsQuery);
$groups = mysqli_fetch_all($groupsResult, MYSQLI_ASSOC);

// Get top customers
$topCustomersQuery = "SELECT 
                     u.id,
                     u.name,
                     u.email,
                     u.customer_group_id,
                     cg.name as group_name,
                     COUNT(o.id) as order_count,
                     SUM(o.total_amount) as total_spent,
                     MAX(o.created_at) as last_order_date
                   FROM users u
                   LEFT JOIN orders o ON u.id = o.user_id
                   LEFT JOIN customer_groups cg ON u.customer_group_id = cg.id
                   GROUP BY u.id
                   ORDER BY total_spent DESC
                   LIMIT 10";
$topCustomersResult = mysqli_query($conn, $topCustomersQuery);
$topCustomers = mysqli_fetch_all($topCustomersResult, MYSQLI_ASSOC);

// Get loyalty program settings
$settingsQuery = "SELECT * FROM loyalty_settings WHERE id = 1";
$settingsResult = mysqli_query($conn, $settingsQuery);

if(mysqli_num_rows($settingsResult) === 0) {
    // Create default settings if they don't exist
    $insertSettingsQuery = "INSERT INTO loyalty_settings (id, points_per_inr, points_redemption_value, min_points_redemption, welcome_points, birthday_points, enabled) 
                           VALUES (1, 1, 0.5, 100, 50, 100, 1)";
    mysqli_query($conn, $insertSettingsQuery);
    
    // Get the newly created settings
    $settingsResult = mysqli_query($conn, $settingsQuery);
}

$settings = mysqli_fetch_assoc($settingsResult);

// Handle loyalty settings update
if(isset($_POST['update_settings'])) {
    $pointsPerInr = (float)$_POST['points_per_inr'];
    $pointsRedemptionValue = (float)$_POST['points_redemption_value'];
    $minPointsRedemption = (int)$_POST['min_points_redemption'];
    $welcomePoints = (int)$_POST['welcome_points'];
    $birthdayPoints = (int)$_POST['birthday_points'];
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    $updateSettingsQuery = "UPDATE loyalty_settings SET 
                           points_per_inr = $pointsPerInr,
                           points_redemption_value = $pointsRedemptionValue,
                           min_points_redemption = $minPointsRedemption,
                           welcome_points = $welcomePoints,
                           birthday_points = $birthdayPoints,
                           enabled = $enabled
                           WHERE id = 1";
    
    $updateSettingsResult = mysqli_query($conn, $updateSettingsQuery);
    
    if($updateSettingsResult) {
        $successMessage = "Loyalty program settings updated successfully";
        
        // Refresh settings
        $settingsResult = mysqli_query($conn, $settingsQuery);
        $settings = mysqli_fetch_assoc($settingsResult);
    } else {
        $errorMessage = "Failed to update loyalty program settings: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Loyalty Program - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .loyalty-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 992px) {
            .loyalty-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .customer-group {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            position: relative;
        }
        
        .customer-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .customer-group-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        .customer-group-discount {
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .customer-group-stats {
            display: flex;
            margin-bottom: 15px;
        }
        
        .customer-group-stat {
            flex: 1;
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .customer-group-stat:last-child {
            margin-right: 0;
        }
        
        .customer-group-stat-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .customer-group-stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .customer-group-description {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .customer-group-actions {
            display: flex;
            justify-content: flex-end;
        }
        
        .customer-group-actions .btn-sm {
            margin-left: 10px;
        }
        
        .add-group-form {
            margin-bottom: 30px;
        }
        
        .add-group-form .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .add-group-form .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .loyalty-settings {
            margin-bottom: 30px;
        }
        
        .loyalty-settings .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .loyalty-settings .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .customer-table img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
        }
        
        .customer-name {
            font-weight: bold;
        }
        
        .customer-email {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .group-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            background-color: #f8f9fa;
            color: #2c3e50;
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
            
            <!-- Customer Loyalty Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Customer Loyalty Program</h1>
                </div>
                
                <?php if(isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>
                
                <div class="loyalty-grid">
                    <!-- Customer Groups -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Customer Groups</h2>
                        </div>
                        <div class="card-body">
                            <!-- Add New Group Form -->
                            <div class="add-group-form">
                                <h3>Add New Customer Group</h3>
                                <form action="" method="POST">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="name">Group Name</label>
                                            <input type="text" id="name" name="name" class="form-control" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="discount_percentage">Discount Percentage</label>
                                            <input type="number" id="discount_percentage" name="discount_percentage" class="form-control" min="0" max="100" step="0.1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="min_order_count">Min. Order Count</label>
                                            <input type="number" id="min_order_count" name="min_order_count" class="form-control" min="0" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="min_total_spent">Min. Total Spent (₹)</label>
                                            <input type="number" id="min_total_spent" name="min_total_spent" class="form-control" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="create_group" class="btn btn-primary">Create Group</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Customer Groups List -->
                            <div class="customer-groups-list">
                                <h3>Existing Groups</h3>
                                
                                <?php if(count($groups) > 0): ?>
                                    <?php foreach($groups as $group): ?>
                                        <div class="customer-group">
                                            <div class="customer-group-header">
                                                <h4 class="customer-group-name"><?php echo $group['name']; ?></h4>
                                                <span class="customer-group-discount"><?php echo $group['discount_percentage']; ?>% Discount</span>
                                            </div>
                                            
                                            <div class="customer-group-stats">
                                                <div class="customer-group-stat">
                                                    <div class="customer-group-stat-label">Customers</div>
                                                    <div class="customer-group-stat-value"><?php echo $group['customer_count']; ?></div>
                                                </div>
                                                
                                                <div class="customer-group-stat">
                                                    <div class="customer-group-stat-label">Min. Orders</div>
                                                    <div class="customer-group-stat-value"><?php echo $group['min_order_count']; ?></div>
                                                </div>
                                                
                                                <div class="customer-group-stat">
                                                    <div class="customer-group-stat-label">Min. Spent</div>
                                                    <div class="customer-group-stat-value">₹<?php echo number_format($group['min_total_spent'], 2); ?></div>
                                                </div>
                                            </div>
                                            
                                            <?php if($group['description']): ?>
                                                <div class="customer-group-description">
                                                    <?php echo $group['description']; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="customer-group-actions">
                                                <button class="btn-sm btn-primary edit-group-btn" data-group-id="<?php echo $group['id']; ?>" data-group-name="<?php echo $group['name']; ?>" data-group-discount="<?php echo $group['discount_percentage']; ?>" data-group-min-orders="<?php echo $group['min_order_count']; ?>" data-group-min-spent="<?php echo $group['min_total_spent']; ?>" data-group-description="<?php echo $group['description']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                
                                                <?php if($group['customer_count'] == 0): ?>
                                                    <a href="?delete_group=<?php echo $group['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this group?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center">No customer groups found</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Auto-assign Groups -->
                            <div class="auto-assign-section">
                                <h3>Auto-assign Groups</h3>
                                <p>Automatically assign customers to groups based on their order history.</p>
                                
                                <form action="" method="POST">
                                    <button type="submit" name="auto_assign" class="btn btn-primary">Auto-assign All Customers</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loyalty Program Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Loyalty Program Settings</h2>
                        </div>
                        <div class="card-body">
                            <div class="loyalty-settings">
                                <form action="" method="POST">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="points_per_inr">Points per ₹1 Spent</label>
                                            <input type="number" id="points_per_inr" name="points_per_inr" class="form-control" min="0.1" step="0.1" value="<?php echo $settings['points_per_inr']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="points_redemption_value">Redemption Value (₹ per point)</label>
                                            <input type="number" id="points_redemption_value" name="points_redemption_value" class="form-control" min="0.01" step="0.01" value="<?php echo $settings['points_redemption_value']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="min_points_redemption">Minimum Points for Redemption</label>
                                            <input type="number" id="min_points_redemption" name="min_points_redemption" class="form-control" min="1" value="<?php echo $settings['min_points_redemption']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="welcome_points">Welcome Points</label>
                                            <input type="number" id="welcome_points" name="welcome_points" class="form-control" min="0" value="<?php echo $settings['welcome_points']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="birthday_points">Birthday Points</label>
                                            <input type="number" id="birthday_points" name="birthday_points" class="form-control" min="0" value="<?php echo $settings['birthday_points']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group checkbox-group">
                                            <input type="checkbox" id="enabled" name="enabled" <?php echo $settings['enabled'] ? 'checked' : ''; ?>>
                                            <label for="enabled">Enable Loyalty Program</label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Top Customers -->
                            <div class="top-customers">
                                <h3>Top Customers</h3>
                                
                                <div class="table-responsive">
                                    <table class="data-table customer-table">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Orders</th>
                                                <th>Total Spent</th>
                                                <th>Group</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($topCustomers as $customer): ?>
                                                <tr>
                                                    <td>
                                                        <div class="customer-info">
                                                            <div>
                                                                <div class="customer-name"><?php echo $customer['name']; ?></div>
                                                                <div class="customer-email"><?php echo $customer['email']; ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $customer['order_count']; ?></td>
                                                    <td>₹<?php echo number_format($customer['total_spent'], 2); ?></td>
                                                    <td>
                                                        <?php if($customer['group_name']): ?>
                                                            <span class="group-badge"><?php echo $customer['group_name']; ?></span>
                                                        <?php else: ?>
                                                            <span class="group-badge">None</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn-sm btn-primary assign-group-btn" data-user-id="<?php echo $customer['id']; ?>" data-user-name="<?php echo $customer['name']; ?>" data-group-id="<?php echo $customer['customer_group_id']; ?>">
                                                            <i class="fas fa-user-tag"></i> Assign Group
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Group Modal -->
    <div class="modal" id="editGroupModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Customer Group</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <input type="hidden" id="edit_group_id" name="group_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Group Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_discount_percentage">Discount Percentage</label>
                            <input type="number" id="edit_discount_percentage" name="discount_percentage" class="form-control" min="0" max="100" step="0.1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_min_order_count">Min. Order Count</label>
                            <input type="number" id="edit_min_order_count" name="min_order_count" class="form-control" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_min_total_spent">Min. Total Spent (₹)</label>
                        <input type="number" id="edit_min_total_spent" name="min_total_spent" class="form-control" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_group" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Assign Group Modal -->
    <div class="modal" id="assignGroupModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Customer Group</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <input type="hidden" id="assign_user_id" name="user_id">
                    
                    <div class="form-group">
                        <label for="assign_user_name">Customer</label>
                        <input type="text" id="assign_user_name" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="assign_group_id">Customer Group</label>
                        <select id="assign_group_id" name="group_id" class="form-control" required>
                            <option value="">None</option>
                            <?php foreach($groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?> (<?php echo $group['discount_percentage']; ?>% Discount)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="assign_group" class="btn btn-primary">Assign Group</button>
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Group Modal
            const editModal = document.getElementById('editGroupModal');
            const editButtons = document.querySelectorAll('.edit-group-btn');
            const editCloseButtons = editModal.querySelectorAll('.close, .close-modal');
            
            // Open modal when edit button is clicked
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const groupId = this.getAttribute('data-group-id');
                    const groupName = this.getAttribute('data-group-name');
                    const groupDiscount = this.getAttribute('data-group-discount');
                    const groupMinOrders = this.getAttribute('data-group-min-orders');
                    const groupMinSpent = this.getAttribute('data-group-min-spent');
                    const groupDescription = this.getAttribute('data-group-description');
                    
                    document.getElementById('edit_group_id').value = groupId;
                    document.getElementById('edit_name').value = groupName;
                    document.getElementById('edit_discount_percentage').value = groupDiscount;
                    document.getElementById('edit_min_order_count').value = groupMinOrders;
                    document.getElementById('edit_min_total_spent').value = groupMinSpent;
                    document.getElementById('edit_description').value = groupDescription;
                    
                    editModal.style.display = 'block';
                });
            });
            
            // Close modal when close button is clicked
            editCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    editModal.style.display = 'none';
                });
            });
            
            // Assign Group Modal
            const assignModal = document.getElementById('assignGroupModal');
            const assignButtons = document.querySelectorAll('.assign-group-btn');
            const assignCloseButtons = assignModal.querySelectorAll('.close, .close-modal');
            
            // Open modal when assign button is clicked
            assignButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    const groupId = this.getAttribute('data-group-id');
                    
                    document.getElementById('assign_user_id').value = userId;
                    document.getElementById('assign_user_name').value = userName;
                    
                    const groupSelect = document.getElementById('assign_group_id');
                    if(groupId) {
                        groupSelect.value = groupId;
                    } else {
                        groupSelect.value = '';
                    }
                    
                    assignModal.style.display = 'block';
                });
            });
            
            // Close modal when close button is clicked
            assignCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    assignModal.style.display = 'none';
                });
            });
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === editModal) {
                    editModal.style.display = 'none';
                }
                
                if (event.target === assignModal) {
                    assignModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
