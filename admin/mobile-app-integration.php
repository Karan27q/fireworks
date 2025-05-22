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

// Get API settings
$api_settings_query = "SELECT * FROM site_options WHERE option_name LIKE 'api_%'";
$api_settings_result = mysqli_query($conn, $api_settings_query);
$api_settings = [];

while ($row = mysqli_fetch_assoc($api_settings_result)) {
    $key = str_replace('api_', '', $row['option_name']);
    $api_settings[$key] = $row['option_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update API settings
    if (isset($_POST['update_api_settings'])) {
        $api_enabled = isset($_POST['api_enabled']) ? 1 : 0;
        $api_key = isset($_POST['api_key']) ? $_POST['api_key'] : generate_api_key();
        $api_secret = isset($_POST['api_secret']) ? $_POST['api_secret'] : generate_api_secret();
        $api_rate_limit = isset($_POST['api_rate_limit']) ? intval($_POST['api_rate_limit']) : 100;
        
        // Update settings in database
        $settings = [
            'api_enabled' => $api_enabled,
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'api_rate_limit' => $api_rate_limit
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
        
        // Update local array
        $api_settings = [
            'enabled' => $api_enabled,
            'key' => $api_key,
            'secret' => $api_secret,
            'rate_limit' => $api_rate_limit
        ];
        
        $message = "API settings updated successfully.";
    }
    
    // Generate new API key
    if (isset($_POST['generate_api_key'])) {
        $api_key = generate_api_key();
        $update_query = "UPDATE site_options SET option_value = '$api_key' WHERE option_name = 'api_key'";
        
        if (mysqli_query($conn, $update_query)) {
            $api_settings['key'] = $api_key;
            $message = "New API key generated successfully.";
        } else {
            $error = "Failed to generate new API key.";
        }
    }
    
    // Generate new API secret
    if (isset($_POST['generate_api_secret'])) {
        $api_secret = generate_api_secret();
        $update_query = "UPDATE site_options SET option_value = '$api_secret' WHERE option_name = 'api_secret'";
        
        if (mysqli_query($conn, $update_query)) {
            $api_settings['secret'] = $api_secret;
            $message = "New API secret generated successfully.";
        } else {
            $error = "Failed to generate new API secret.";
        }
    }
}

// Helper functions
function generate_api_key() {
    return bin2hex(random_bytes(16));
}

function generate_api_secret() {
    return bin2hex(random_bytes(32));
}

// Get API usage statistics
$api_requests_query = "SELECT COUNT(*) as total_requests, 
                      SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as successful_requests,
                      SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as failed_requests
                      FROM api_logs";
$api_requests_result = mysqli_query($conn, $api_requests_query);
$api_requests = mysqli_fetch_assoc($api_requests_result);

// Get recent API requests
$recent_requests_query = "SELECT * FROM api_logs ORDER BY created_at DESC LIMIT 10";
$recent_requests_result = mysqli_query($conn, $recent_requests_query);
$recent_requests = mysqli_fetch_all($recent_requests_result, MYSQLI_ASSOC);

// Get API endpoints
$endpoints = [
    [
        'method' => 'GET',
        'endpoint' => '/api/products',
        'description' => 'Get all products or filter by category',
        'parameters' => 'category_id, search, page, limit',
        'example' => '/api/products?category_id=1&limit=10'
    ],
    [
        'method' => 'GET',
        'endpoint' => '/api/products/{id}',
        'description' => 'Get a specific product by ID',
        'parameters' => 'None',
        'example' => '/api/products/123'
    ],
    [
        'method' => 'GET',
        'endpoint' => '/api/categories',
        'description' => 'Get all categories',
        'parameters' => 'None',
        'example' => '/api/categories'
    ],
    [
        'method' => 'POST',
        'endpoint' => '/api/orders',
        'description' => 'Create a new order',
        'parameters' => 'user_id, products, shipping_address, payment_method',
        'example' => 'POST /api/orders with JSON body'
    ],
    [
        'method' => 'GET',
        'endpoint' => '/api/orders/{id}',
        'description' => 'Get a specific order by ID',
        'parameters' => 'None',
        'example' => '/api/orders/456'
    ],
    [
        'method' => 'GET',
        'endpoint' => '/api/user/orders',
        'description' => 'Get orders for the authenticated user',
        'parameters' => 'page, limit',
        'example' => '/api/user/orders?page=1&limit=10'
    ],
    [
        'method' => 'POST',
        'endpoint' => '/api/auth/login',
        'description' => 'Authenticate user and get token',
        'parameters' => 'email, password',
        'example' => 'POST /api/auth/login with JSON body'
    ],
    [
        'method' => 'POST',
        'endpoint' => '/api/auth/register',
        'description' => 'Register a new user',
        'parameters' => 'name, email, password, phone',
        'example' => 'POST /api/auth/register with JSON body'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile App Integration - Fireworks Shop</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .api-key-container {
            display: flex;
            align-items: center;
        }
        
        .api-key-field {
            flex: 1;
            position: relative;
        }
        
        .api-key-value {
            padding-right: 40px;
            font-family: monospace;
            word-break: break-all;
        }
        
        .copy-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #4caf50;
            cursor: pointer;
        }
        
        .endpoint-table th:first-child {
            width: 100px;
        }
        
        .endpoint-table th:nth-child(2) {
            width: 200px;
        }
        
        .endpoint-example {
            font-family: monospace;
            background-color: #f5f5f5;
            padding: 5px;
            border-radius: 4px;
        }
        
        .api-stats {
            display: flex;
            margin-bottom: 20px;
        }
        
        .api-stat {
            flex: 1;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin-right: 10px;
            text-align: center;
        }
        
        .api-stat:last-child {
            margin-right: 0;
        }
        
        .api-stat h3 {
            margin-top: 0;
            color: #333;
        }
        
        .api-stat p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
        }
        
        .api-stat.success p {
            color: #4caf50;
        }
        
        .api-stat.error p {
            color: #f44336;
        }
        
        .qr-code-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            background-color: #f5f5f5;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
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
                    <h1>Mobile App Integration</h1>
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
                                    <h2>API Settings</h2>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="api_enabled" name="api_enabled" value="1" <?php echo isset($api_settings['enabled']) && $api_settings['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="api_enabled">Enable API</label>
                                            </div>
                                            <small class="form-text text-muted">Enable or disable the API for mobile app integration.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="api_key">API Key</label>
                                            <div class="api-key-container">
                                                <div class="api-key-field">
                                                    <input type="text" id="api_key" name="api_key" class="form-control" value="<?php echo isset($api_settings['key']) ? $api_settings['key'] : ''; ?>" readonly>
                                                    <button type="button" class="copy-btn" onclick="copyToClipboard('api_key')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <button type="submit" name="generate_api_key" class="btn btn-sm btn-secondary ml-2">
                                                    <i class="fas fa-sync-alt"></i> Generate New
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">This key is used to authenticate API requests.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="api_secret">API Secret</label>
                                            <div class="api-key-container">
                                                <div class="api-key-field">
                                                    <input type="text" id="api_secret" name="api_secret" class="form-control" value="<?php echo isset($api_settings['secret']) ? $api_settings['secret'] : ''; ?>" readonly>
                                                    <button type="button" class="copy-btn" onclick="copyToClipboard('api_secret')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <button type="submit" name="generate_api_secret" class="btn btn-sm btn-secondary ml-2">
                                                    <i class="fas fa-sync-alt"></i> Generate New
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">This secret is used to sign API requests.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="api_rate_limit">Rate Limit (requests per minute)</label>
                                            <input type="number" id="api_rate_limit" name="api_rate_limit" class="form-control" value="<?php echo isset($api_settings['rate_limit']) ? $api_settings['rate_limit'] : 100; ?>" min="10" max="1000">
                                            <small class="form-text text-muted">Maximum number of API requests allowed per minute per client.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="update_api_settings" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>API Endpoints</h2>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="data-table endpoint-table">
                                            <thead>
                                                <tr>
                                                    <th>Method</th>
                                                    <th>Endpoint</th>
                                                    <th>Description</th>
                                                    <th>Parameters</th>
                                                    <th>Example</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($endpoints as $endpoint): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge badge-<?php echo $endpoint['method'] == 'GET' ? 'success' : 'primary'; ?>">
                                                                <?php echo $endpoint['method']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $endpoint['endpoint']; ?></td>
                                                        <td><?php echo $endpoint['description']; ?></td>
                                                        <td><?php echo $endpoint['parameters']; ?></td>
                                                        <td>
                                                            <span class="endpoint-example"><?php echo $endpoint['example']; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h2>API Usage</h2>
                                </div>
                                <div class="card-body">
                                    <div class="api-stats">
                                        <div class="api-stat">
                                            <h3>Total Requests</h3>
                                            <p><?php echo number_format($api_requests['total_requests'] ?? 0); ?></p>
                                        </div>
                                        
                                        <div class="api-stat success">
                                            <h3>Successful</h3>
                                            <p><?php echo number_format($api_requests['successful_requests'] ?? 0); ?></p>
                                        </div>
                                        
                                        <div class="api-stat error">
                                            <h3>Failed</h3>
                                            <p><?php echo number_format($api_requests['failed_requests'] ?? 0); ?></p>
                                        </div>
                                    </div>
                                    
                                    <h3>Recent API Requests</h3>
                                    <div class="table-responsive">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Endpoint</th>
                                                    <th>Method</th>
                                                    <th>Status</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(count($recent_requests) > 0): ?>
                                                    <?php foreach($recent_requests as $request): ?>
                                                        <tr>
                                                            <td><?php echo $request['endpoint']; ?></td>
                                                            <td><?php echo $request['method']; ?></td>
                                                            <td>
                                                                <span class="badge badge-<?php echo ($request['status_code'] >= 200 && $request['status_code'] < 300) ? 'success' : 'danger'; ?>">
                                                                    <?php echo $request['status_code']; ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('d M H:i', strtotime($request['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No API requests found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Mobile App QR Code</h2>
                                </div>
                                <div class="card-body">
                                    <p>Scan this QR code with your mobile app to connect to this store:</p>
                                    
                                    <div class="qr-code-container">
                                        <div class="qr-code">
                                            <i class="fas fa-qrcode fa-5x"></i>
                                        </div>
                                        <p class="mt-2">App Connection QR Code</p>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <button class="btn btn-primary">
                                            <i class="fas fa-download"></i> Download QR Code
                                        </button>
                                    </div>
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
        // Function to copy text to clipboard
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');
            
            // Show feedback
            const copyBtn = element.nextElementSibling;
            const originalIcon = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i>';
            
            setTimeout(() => {
                copyBtn.innerHTML = originalIcon;
            }, 2000);
        }
    </script>
</body>
</html>
