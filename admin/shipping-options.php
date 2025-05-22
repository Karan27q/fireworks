<?php
session_start();

// Define admin path constant
define('ADMIN_PATH', true);

// Include database connection
include '../includes/db_connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit Shipping Method
    if (isset($_POST['save_shipping_method'])) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = floatval($_POST['price']);
        $delivery_time = mysqli_real_escape_string($conn, $_POST['delivery_time']);
        $active = isset($_POST['active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = "Shipping method name is required.";
        } else {
            if ($id > 0) {
                // Update existing shipping method
                $query = "UPDATE shipping_methods SET 
                          name = '$name', 
                          description = '$description', 
                          price = $price, 
                          delivery_time = '$delivery_time', 
                          active = $active 
                          WHERE id = $id";
                
                if (mysqli_query($conn, $query)) {
                    $message = "Shipping method updated successfully.";
                } else {
                    $error = "Error updating shipping method: " . mysqli_error($conn);
                }
            } else {
                // Add new shipping method
                $query = "INSERT INTO shipping_methods (name, description, price, delivery_time, active) 
                          VALUES ('$name', '$description', $price, '$delivery_time', $active)";
                
                if (mysqli_query($conn, $query)) {
                    $message = "Shipping method added successfully.";
                } else {
                    $error = "Error adding shipping method: " . mysqli_error($conn);
                }
            }
        }
    }
    
    // Delete Shipping Method
    if (isset($_POST['delete_shipping_method'])) {
        $id = intval($_POST['id']);
        
        // Check if shipping method is used in orders
        $check_query = "SELECT COUNT(*) as count FROM orders WHERE shipping_method_id = $id";
        $check_result = mysqli_query($conn, $check_query);
        $check_data = mysqli_fetch_assoc($check_result);
        
        if ($check_data['count'] > 0) {
            $error = "Cannot delete shipping method as it is used in orders.";
        } else {
            $query = "DELETE FROM shipping_methods WHERE id = $id";
            
            if (mysqli_query($conn, $query)) {
                $message = "Shipping method deleted successfully.";
            } else {
                $error = "Error deleting shipping method: " . mysqli_error($conn);
            }
        }
    }
    
    // Update Shipping Zones
    if (isset($_POST['save_shipping_zones'])) {
        $zones = isset($_POST['zones']) ? $_POST['zones'] : [];
        
        // First, delete all existing zones
        $delete_query = "DELETE FROM shipping_zones";
        mysqli_query($conn, $delete_query);
        
        // Then insert new zones
        foreach ($zones as $zone) {
            $name = mysqli_real_escape_string($conn, $zone['name']);
            $regions = mysqli_real_escape_string($conn, $zone['regions']);
            $price_adjustment = floatval($zone['price_adjustment']);
            
            $insert_query = "INSERT INTO shipping_zones (name, regions, price_adjustment) 
                            VALUES ('$name', '$regions', $price_adjustment)";
            mysqli_query($conn, $insert_query);
        }
        
        $message = "Shipping zones updated successfully.";
    }
    
    // Update Shipping Settings
    if (isset($_POST['save_shipping_settings'])) {
        $free_shipping_threshold = floatval($_POST['free_shipping_threshold']);
        $enable_local_pickup = isset($_POST['enable_local_pickup']) ? 1 : 0;
        $local_pickup_discount = floatval($_POST['local_pickup_discount']);
        $enable_shipping_calculator = isset($_POST['enable_shipping_calculator']) ? 1 : 0;
        
        // Update settings
        $settings = [
            'shipping_free_threshold' => $free_shipping_threshold,
            'shipping_enable_local_pickup' => $enable_local_pickup,
            'shipping_local_pickup_discount' => $local_pickup_discount,
            'shipping_enable_calculator' => $enable_shipping_calculator
        ];
        
        foreach ($settings as $option_name => $option_value) {
            $check_query = "SELECT * FROM site_options WHERE option_name = '$option_name'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $update_query = "UPDATE site_options SET option_value = '$option_value' WHERE option_name = '$option_name'";
                mysqli_query($conn, $update_query);
            } else {
                $insert_query = "INSERT INTO site_options (option_name, option_value) VALUES ('$option_name', '$option_value')";
                mysqli_query($conn, $insert_query);
            }
        }
        
        $message = "Shipping settings updated successfully.";
    }
}

// Get shipping methods
$shipping_methods_query = "SELECT * FROM shipping_methods ORDER BY name ASC";
$shipping_methods_result = mysqli_query($conn, $shipping_methods_query);
$shipping_methods = mysqli_fetch_all($shipping_methods_result, MYSQLI_ASSOC);

// Get shipping zones
$shipping_zones_query = "SELECT * FROM shipping_zones ORDER BY name ASC";
$shipping_zones_result = mysqli_query($conn, $shipping_zones_query);
$shipping_zones = mysqli_fetch_all($shipping_zones_result, MYSQLI_ASSOC);

// Get shipping settings
$shipping_settings_query = "SELECT * FROM site_options WHERE option_name LIKE 'shipping_%'";
$shipping_settings_result = mysqli_query($conn, $shipping_settings_query);
$shipping_settings = [];

while ($row = mysqli_fetch_assoc($shipping_settings_result)) {
    $key = str_replace('shipping_', '', $row['option_name']);
    $shipping_settings[$key] = $row['option_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Options - Fireworks Shop</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .shipping-zones-container {
            margin-bottom: 20px;
        }
        
        .shipping-zone {
            background-color: #f5f5f5;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .shipping-zone .remove-zone {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #f44336;
            cursor: pointer;
        }
        
        .add-zone-btn {
            margin-top: 10px;
        }
        
        .shipping-method {
            background-color: #f5f5f5;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .shipping-method-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .shipping-method-body {
            margin-top: 15px;
            display: none;
        }
        
        .shipping-method.active .shipping-method-body {
            display: block;
        }
        
        .shipping-method-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .shipping-method-status.active {
            background-color: #4caf50;
        }
        
        .shipping-method-status.inactive {
            background-color: #f44336;
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
            
            <!-- Page Content -->
            <div class="content">
                <div class="content-header">
                    <h1>Shipping Options</h1>
                    <div class="actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if($message): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="content-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Shipping Methods</h2>
                                    <button class="btn btn-sm btn-primary" id="add-shipping-method-btn">
                                        <i class="fas fa-plus"></i> Add Shipping Method
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="shipping-methods-container">
                                        <?php if(count($shipping_methods) > 0): ?>
                                            <?php foreach($shipping_methods as $method): ?>
                                                <div class="shipping-method" data-id="<?php echo $method['id']; ?>">
                                                    <div class="shipping-method-header">
                                                        <div>
                                                            <span class="shipping-method-status <?php echo $method['active'] ? 'active' : 'inactive'; ?>"></span>
                                                            <strong><?php echo $method['name']; ?></strong>
                                                            <span class="text-muted ml-2">₹<?php echo number_format($method['price'], 2); ?></span>
                                                        </div>
                                                        <i class="fas fa-chevron-down"></i>
                                                    </div>
                                                    <div class="shipping-method-body">
                                                        <form action="" method="POST">
                                                            <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                                                            
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="name-<?php echo $method['id']; ?>">Name</label>
                                                                    <input type="text" id="name-<?php echo $method['id']; ?>" name="name" class="form-control" value="<?php echo $method['name']; ?>" required>
                                                                </div>
                                                                
                                                                <div class="form-group col-md-6">
                                                                    <label for="price-<?php echo $method['id']; ?>">Price (₹)</label>
                                                                    <input type="number" id="price-<?php echo $method['id']; ?>" name="price" class="form-control" value="<?php echo $method['price']; ?>" step="0.01" min="0" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="description-<?php echo $method['id']; ?>">Description</label>
                                                                <textarea id="description-<?php echo $method['id']; ?>" name="description" class="form-control" rows="2"><?php echo $method['description']; ?></textarea>
                                                            </div>
                                                            
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="delivery-time-<?php echo $method['id']; ?>">Delivery Time</label>
                                                                    <input type="text" id="delivery-time-<?php echo $method['id']; ?>" name="delivery_time" class="form-control" value="<?php echo $method['delivery_time']; ?>" placeholder="e.g. 2-3 business days">
                                                                </div>
                                                                
                                                                <div class="form-group col-md-6">
                                                                    <div class="custom-control custom-switch mt-4">
                                                                        <input type="checkbox" class="custom-control-input" id="active-<?php echo $method['id']; ?>" name="active" value="1" <?php echo $method['active'] ? 'checked' : ''; ?>>
                                                                        <label class="custom-control-label" for="active-<?php echo $method['id']; ?>">Active</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <button type="submit" name="save_shipping_method" class="btn btn-primary">
                                                                    <i class="fas fa-save"></i> Save Changes
                                                                </button>
                                                                
                                                                <button type="submit" name="delete_shipping_method" class="btn btn-danger float-right" onclick="return confirm('Are you sure you want to delete this shipping method?');">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                No shipping methods found. Click "Add Shipping Method" to create one.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Add Shipping Method Form (Hidden by default) -->
                                    <div id="add-shipping-method-form" style="display: none;">
                                        <h3>Add New Shipping Method</h3>
                                        <form action="" method="POST">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="name-new">Name</label>
                                                    <input type="text" id="name-new" name="name" class="form-control" required>
                                                </div>
                                                
                                                <div class="form-group col-md-6">
                                                    <label for="price-new">Price (₹)</label>
                                                    <input type="number" id="price-new" name="price" class="form-control" step="0.01" min="0" required>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description-new">Description</label>
                                                <textarea id="description-new" name="description" class="form-control" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="delivery-time-new">Delivery Time</label>
                                                    <input type="text" id="delivery-time-new" name="delivery_time" class="form-control" placeholder="e.g. 2-3 business days">
                                                </div>
                                                
                                                <div class="form-group col-md-6">
                                                    <div class="custom-control custom-switch mt-4">
                                                        <input type="checkbox" class="custom-control-input" id="active-new" name="active" value="1" checked>
                                                        <label class="custom-control-label" for="active-new">Active</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <button type="submit" name="save_shipping_method" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Add Shipping Method
                                                </button>
                                                
                                                <button type="button" id="cancel-add-shipping-method" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Shipping Zones</h2>
                                </div>
                                <div class="card-body">
                                    <p>Define shipping zones to apply different shipping rates based on customer location.</p>
                                    
                                    <form action="" method="POST" id="shipping-zones-form">
                                        <div id="shipping-zones-container" class="shipping-zones-container">
                                            <?php if(count($shipping_zones) > 0): ?>
                                                <?php foreach($shipping_zones as $index => $zone): ?>
                                                    <div class="shipping-zone">
                                                        <span class="remove-zone"><i class="fas fa-times"></i></span>
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Zone Name</label>
                                                                <input type="text" name="zones[<?php echo $index; ?>][name]" class="form-control" value="<?php echo $zone['name']; ?>" required>
                                                            </div>
                                                            
                                                            <div class="form-group col-md-5">
                                                                <label>Regions</label>
                                                                <input type="text" name="zones[<?php echo $index; ?>][regions]" class="form-control" value="<?php echo $zone['regions']; ?>" required placeholder="e.g. Delhi, Mumbai, Karnataka">
                                                                <small class="form-text text-muted">Comma-separated list of regions</small>
                                                            </div>
                                                            
                                                            <div class="form-group col-md-3">
                                                                <label>Price Adjustment (%)</label>
                                                                <input type="number" name="zones[<?php echo $index; ?>][price_adjustment]" class="form-control" value="<?php echo $zone['price_adjustment']; ?>" step="0.01" required>
                                                                <small class="form-text text-muted">% increase/decrease</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="shipping-zone">
                                                    <span class="remove-zone"><i class="fas fa-times"></i></span>
                                                    
                                                    <div class="form-row">
                                                        <div class="form-group col-md-4">
                                                            <label>Zone Name</label>
                                                            <input type="text" name="zones[0][name]" class="form-control" required placeholder="e.g. North India">
                                                        </div>
                                                        
                                                        <div class="form-group col-md-5">
                                                            <label>Regions</label>
                                                            <input type="text" name="zones[0][regions]" class="form-control" required placeholder="e.g. Delhi, Punjab, Haryana">
                                                            <small class="form-text text-muted">Comma-separated list of regions</small>
                                                        </div>
                                                        
                                                        <div class="form-group col-md-3">
                                                            <label>Price Adjustment (%)</label>
                                                            <input type="number" name="zones[0][price_adjustment]" class="form-control" value="0" step="0.01" required>
                                                            <small class="form-text text-muted">% increase/decrease</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button type="button" id="add-zone-btn" class="btn btn-sm btn-secondary add-zone-btn">
                                            <i class="fas fa-plus"></i> Add Zone
                                        </button>
                                        
                                        <div class="form-group mt-3">
                                            <button type="submit" name="save_shipping_zones" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Zones
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Shipping Settings</h2>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="free_shipping_threshold">Free Shipping Threshold (₹)</label>
                                            <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" class="form-control" value="<?php echo isset($shipping_settings['free_threshold']) ? $shipping_settings['free_threshold'] : 0; ?>" min="0" step="0.01">
                                            <small class="form-text text-muted">Set to 0 to disable free shipping</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enable_local_pickup" name="enable_local_pickup" value="1" <?php echo isset($shipping_settings['enable_local_pickup']) && $shipping_settings['enable_local_pickup'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="enable_local_pickup">Enable Local Pickup</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="local_pickup_discount">Local Pickup Discount (%)</label>
                                            <input type="number" id="local_pickup_discount" name="local_pickup_discount" class="form-control" value="<?php echo isset($shipping_settings['local_pickup_discount']) ? $shipping_settings['local_pickup_discount'] : 0; ?>" min="0" max="100" step="0.01">
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enable_shipping_calculator" name="enable_shipping_calculator" value="1" <?php echo isset($shipping_settings['enable_calculator']) && $shipping_settings['enable_calculator'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="enable_shipping_calculator">Enable Shipping Calculator on Cart Page</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="save_shipping_settings" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Shipping Help</h2>
                                </div>
                                <div class="card-body">
                                    <h4>Setting Up Shipping</h4>
                                    <ol>
                                        <li>Create shipping methods with different prices and delivery times.</li>
                                        <li>Set up shipping zones if you want to charge different rates based on location.</li>
                                        <li>Configure free shipping threshold if you want to offer free shipping above a certain order amount.</li>
                                        <li>Enable local pickup if customers can pick up orders from your location.</li>
                                    </ol>
                                    
                                    <h4>Tips</h4>
                                    <ul>
                                        <li>Use clear and descriptive names for shipping methods.</li>
                                        <li>Include estimated delivery times to set customer expectations.</li>
                                        <li>Consider offering free shipping above a certain threshold to encourage larger orders.</li>
                                        <li>Regularly review and update your shipping rates to ensure profitability.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Toggle shipping method details
        document.querySelectorAll('.shipping-method-header').forEach(header => {
            header.addEventListener('click', function() {
                const method = this.closest('.shipping-method');
                method.classList.toggle('active');
                
                const icon = this.querySelector('i');
                if (method.classList.contains('active')) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                } else {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
        });
        
        // Show/hide add shipping method form
        document.getElementById('add-shipping-method-btn').addEventListener('click', function() {
            document.getElementById('add-shipping-method-form').style.display = 'block';
            this.style.display = 'none';
        });
        
        document.getElementById('cancel-add-shipping-method').addEventListener('click', function() {
            document.getElementById('add-shipping-method-form').style.display = 'none';
            document.getElementById('add-shipping-method-btn').style.display = 'inline-block';
        });
        
        // Add shipping zone
        document.getElementById('add-zone-btn').addEventListener('click', function() {
            const container = document.getElementById('shipping-zones-container');
            const zoneCount = container.querySelectorAll('.shipping-zone').length;
            
            const newZone = document.createElement('div');
            newZone.className = 'shipping-zone';
            newZone.innerHTML = `
                <span class="remove-zone"><i class="fas fa-times"></i></span>
                
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Zone Name</label>
                        <input type="text" name="zones[${zoneCount}][name]" class="form-control" required placeholder="e.g. South India">
                    </div>
                    
                    <div class="form-group col-md-5">
                        <label>Regions</label>
                        <input type="text" name="zones[${zoneCount}][regions]" class="form-control" required placeholder="e.g. Tamil Nadu, Kerala, Karnataka">
                        <small class="form-text text-muted">Comma-separated list of regions</small>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label>Price Adjustment (%)</label>
                        <input type="number" name="zones[${zoneCount}][price_adjustment]" class="form-control" value="0" step="0.01" required>
                        <small class="form-text text-muted">% increase/decrease</small>
                    </div>
                </div>
            `;
            
            container.appendChild(newZone);
            
            // Add event listener to the new remove button
            newZone.querySelector('.remove-zone').addEventListener('click', function() {
                container.removeChild(newZone);
                updateZoneIndices();
            });
        });
        
        // Remove shipping zone
        document.querySelectorAll('.remove-zone').forEach(button => {
            button.addEventListener('click', function() {
                const zone = this.closest('.shipping-zone');
                const container = document.getElementById('shipping-zones-container');
                
                if (container.querySelectorAll('.shipping-zone').length > 1) {
                    container.removeChild(zone);
                    updateZoneIndices();
                } else {
                    alert('You must have at least one shipping zone.');
                }
            });
        });
        
        // Update zone indices when zones are removed
        function updateZoneIndices() {
            const zones = document.querySelectorAll('.shipping-zone');
            zones.forEach((zone, index) => {
                zone.querySelectorAll('input').forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace(/zones\[\d+\]/, `zones[${index}]`);
                    input.setAttribute('name', newName);
                });
            });
        }
    </script>
</body>
</html>
