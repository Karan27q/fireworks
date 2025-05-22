<?php
session_start();

// Define admin path constant
define('ADMIN_PATH', true);

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';
$server_info = [];
$database_status = false;
$file_permissions = [];
$php_extensions = [];
$server_requirements = [
    'php_version' => '7.4.0',
    'memory_limit' => '128M',
    'max_execution_time' => 30,
    'upload_max_filesize' => '8M',
    'post_max_size' => '8M',
    'required_extensions' => ['mysqli', 'gd', 'curl', 'mbstring', 'zip', 'json']
];

// Check PHP version
$server_info['php_version'] = phpversion();
$server_info['php_version_status'] = version_compare($server_info['php_version'], $server_requirements['php_version'], '>=');

// Check memory limit
$memory_limit = ini_get('memory_limit');
$server_info['memory_limit'] = $memory_limit;
$memory_limit_bytes = return_bytes($memory_limit);
$required_memory_bytes = return_bytes($server_requirements['memory_limit']);
$server_info['memory_limit_status'] = $memory_limit_bytes >= $required_memory_bytes;

// Check max execution time
$max_execution_time = ini_get('max_execution_time');
$server_info['max_execution_time'] = $max_execution_time;
$server_info['max_execution_time_status'] = $max_execution_time >= $server_requirements['max_execution_time'] || $max_execution_time == 0;

// Check upload max filesize
$upload_max_filesize = ini_get('upload_max_filesize');
$server_info['upload_max_filesize'] = $upload_max_filesize;
$upload_max_filesize_bytes = return_bytes($upload_max_filesize);
$required_upload_bytes = return_bytes($server_requirements['upload_max_filesize']);
$server_info['upload_max_filesize_status'] = $upload_max_filesize_bytes >= $required_upload_bytes;

// Check post max size
$post_max_size = ini_get('post_max_size');
$server_info['post_max_size'] = $post_max_size;
$post_max_size_bytes = return_bytes($post_max_size);
$required_post_bytes = return_bytes($server_requirements['post_max_size']);
$server_info['post_max_size_status'] = $post_max_size_bytes >= $required_post_bytes;

// Check PHP extensions
foreach ($server_requirements['required_extensions'] as $extension) {
    $php_extensions[$extension] = extension_loaded($extension);
}

// Check database connection
include '../includes/db_connect.php';
if ($conn) {
    $database_status = true;
    
    // Get database info
    $server_info['database_server'] = mysqli_get_server_info($conn);
    $server_info['database_client'] = mysqli_get_client_info();
    
    // Check database tables
    $tables_query = "SHOW TABLES";
    $tables_result = mysqli_query($conn, $tables_query);
    $server_info['database_tables'] = mysqli_num_rows($tables_result);
    
    // Check database size
    $size_query = "SELECT 
                    table_schema AS 'Database',
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
                  FROM information_schema.tables
                  WHERE table_schema = DATABASE()
                  GROUP BY table_schema";
    $size_result = mysqli_query($conn, $size_query);
    if ($size_result && $size_row = mysqli_fetch_assoc($size_result)) {
        $server_info['database_size'] = $size_row['Size (MB)'] . ' MB';
    } else {
        $server_info['database_size'] = 'Unknown';
    }
}

// Check file permissions
$directories = [
    '../uploads',
    '../uploads/products',
    '../uploads/categories',
    '../uploads/banners',
    '../uploads/logo',
    '../temp',
    '../assets/css'
];

foreach ($directories as $directory) {
    if (!file_exists($directory)) {
        // Try to create the directory
        if (!mkdir($directory, 0755, true)) {
            $file_permissions[$directory] = [
                'exists' => false,
                'writable' => false,
                'status' => 'Directory does not exist and could not be created'
            ];
            continue;
        }
    }
    
    $file_permissions[$directory] = [
        'exists' => true,
        'writable' => is_writable($directory),
        'status' => is_writable($directory) ? 'Writable' : 'Not writable'
    ];
}

// Check if the server is local or production
$server_info['environment'] = is_local_environment() ? 'Local' : 'Production';

// Helper function to convert PHP size strings to bytes
function return_bytes($size_str) {
    switch (substr($size_str, -1)) {
        case 'K':
        case 'k':
            return (int)$size_str * 1024;
        case 'M':
        case 'm':
            return (int)$size_str * 1048576;
        case 'G':
        case 'g':
            return (int)$size_str * 1073741824;
        default:
            return (int)$size_str;
    }
}

// Helper function to check if the environment is local
function is_local_environment() {
    $local_ips = ['127.0.0.1', '::1'];
    $server_name = strtolower($_SERVER['SERVER_NAME']);
    
    return in_array($_SERVER['REMOTE_ADDR'], $local_ips) || 
           strpos($server_name, 'localhost') !== false || 
           strpos($server_name, '.local') !== false || 
           strpos($server_name, '.test') !== false;
}

// Run test for WhatsApp notification
$whatsapp_test_result = null;
if (isset($_POST['test_whatsapp'])) {
    $phone_number = $_POST['phone_number'];
    
    // Include WhatsApp notification functions
    include_once '../includes/whatsapp_notification.php';
    
    // Send test message
    $result = test_whatsapp_notification($phone_number);
    
    if ($result) {
        $message = "Test WhatsApp message sent successfully to $phone_number.";
    } else {
        $error = "Failed to send test WhatsApp message. Please check your settings.";
    }
    
    $whatsapp_test_result = [
        'success' => $result,
        'phone' => $phone_number,
        'time' => date('Y-m-d H:i:s')
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Check - Vamsi Crackers</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-good {
            background-color: #4caf50;
        }
        
        .status-warning {
            background-color: #ff9800;
        }
        
        .status-bad {
            background-color: #f44336;
        }
        
        .server-info-table td {
            padding: 8px;
        }
        
        .server-info-table td:first-child {
            font-weight: bold;
            width: 200px;
        }
        
        .test-result {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        
        .test-result.success {
            background-color: #e8f5e9;
            border: 1px solid #4caf50;
        }
        
        .test-result.error {
            background-color: #ffebee;
            border: 1px solid #f44336;
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
                    <h1>Server Check</h1>
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
                        <div class="col-md-6">
                            <!-- Server Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h2>Server Information</h2>
                                </div>
                                <div class="card-body">
                                    <table class="server-info-table">
                                        <tr>
                                            <td>Environment</td>
                                            <td>
                                                <?php if($server_info['environment'] == 'Local'): ?>
                                                    <span class="badge badge-info">Local</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Production</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>PHP Version</td>
                                            <td>
                                                <?php echo $server_info['php_version']; ?>
                                                <?php if($server_info['php_version_status']): ?>
                                                    <span class="status-indicator status-good"></span>
                                                <?php else: ?>
                                                    <span class="status-indicator status-bad"></span>
                                                    <small class="text-danger">Minimum required: <?php echo $server_requirements['php_version']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Memory Limit</td>
                                            <td>
                                                <?php echo $server_info['memory_limit']; ?>
                                                <?php if($server_info['memory_limit_status']): ?>
                                                    <span class="status-indicator status-good"></span>
                                                <?php else: ?>
                                                    <span class="status-indicator status-warning"></span>
                                                    <small class="text-warning">Recommended: <?php echo $server_requirements['memory_limit']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Max Execution Time</td>
                                            <td>
                                                <?php echo $server_info['max_execution_time']; ?> seconds
                                                <?php if($server_info['max_execution_time_status']): ?>
                                                    <span class="status-indicator status-good"></span>
                                                <?php else: ?>
                                                    <span class="status-indicator status-warning"></span>
                                                    <small class="text-warning">Recommended: <?php echo $server_requirements['max_execution_time']; ?> seconds</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Upload Max Filesize</td>
                                            <td>
                                                <?php echo $server_info['upload_max_filesize']; ?>
                                                <?php if($server_info['upload_max_filesize_status']): ?>
                                                    <span class="status-indicator status-good"></span>
                                                <?php else: ?>
                                                    <span class="status-indicator status-warning"></span>
                                                    <small class="text-warning">Recommended: <?php echo $server_requirements['upload_max_filesize']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Post Max Size</td>
                                            <td>
                                                <?php echo $server_info['post_max_size']; ?>
                                                <?php if($server_info['post_max_size_status']): ?>
                                                    <span class="status-indicator status-good"></span>
                                                <?php else: ?>
                                                    <span class="status-indicator status-warning"></span>
                                                    <small class="text-warning">Recommended: <?php echo $server_requirements['post_max_size']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Server Software</td>
                                            <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Server Name</td>
                                            <td><?php echo $_SERVER['SERVER_NAME']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Document Root</td>
                                            <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- PHP Extensions -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>PHP Extensions</h2>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach($php_extensions as $extension => $loaded): ?>
                                            <div class="col-md-6 mb-2">
                                                <?php if($loaded): ?>
                                                    <span class="status-indicator status-good"></span>
                                                <?php else: ?>
                                                    <span class="status-indicator status-bad"></span>
                                                <?php endif; ?>
                                                <?php echo $extension; ?>
                                                <?php if(!$loaded): ?>
                                                    <small class="text-danger">(Not loaded)</small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Database Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h2>Database Information</h2>
                                </div>
                                <div class="card-body">
                                    <?php if($database_status): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> Database connection successful
                                        </div>
                                        
                                        <table class="server-info-table">
                                            <tr>
                                                <td>Database Server</td>
                                                <td><?php echo $server_info['database_server']; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Database Client</td>
                                                <td><?php echo $server_info['database_client']; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Database Name</td>
                                                <td><?php echo DB_NAME; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Database Host</td>
                                                <td><?php echo DB_HOST; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Number of Tables</td>
                                                <td><?php echo $server_info['database_tables']; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Database Size</td>
                                                <td><?php echo $server_info['database_size']; ?></td>
                                            </tr>
                                        </table>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times-circle"></i> Database connection failed
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- File Permissions -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>File Permissions</h2>
                                </div>
                                <div class="card-body">
                                    <table class="server-info-table">
                                        <?php foreach($file_permissions as $directory => $permission): ?>
                                            <tr>
                                                <td><?php echo $directory; ?></td>
                                                <td>
                                                    <?php if($permission['exists'] && $permission['writable']): ?>
                                                        <span class="status-indicator status-good"></span> <?php echo $permission['status']; ?>
                                                    <?php elseif($permission['exists'] && !$permission['writable']): ?>
                                                        <span class="status-indicator status-bad"></span> <?php echo $permission['status']; ?>
                                                    <?php else: ?>
                                                        <span class="status-indicator status-bad"></span> <?php echo $permission['status']; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Test WhatsApp Notification -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Test WhatsApp Notification</h2>
                                </div>
                                <div class="card-body">
                                    <p>Send a test WhatsApp message to verify your notification system is working correctly.</p>
                                    
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="phone_number">Phone Number</label>
                                            <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="e.g. 919876543210" required>
                                            <small class="form-text text-muted">Include country code without + (e.g. 91 for India)</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="test_whatsapp" class="btn btn-primary">
                                                <i class="fab fa-whatsapp"></i> Send Test Message
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <?php if($whatsapp_test_result): ?>
                                        <div class="test-result <?php echo $whatsapp_test_result['success'] ? 'success' : 'error'; ?>">
                                            <?php if($whatsapp_test_result['success']): ?>
                                                <p><strong>Test message sent successfully!</strong></p>
                                                <p>Phone: <?php echo $whatsapp_test_result['phone']; ?></p>
                                                <p>Time: <?php echo $whatsapp_test_result['time']; ?></p>
                                            <?php else: ?>
                                                <p><strong>Failed to send test message.</strong></p>
                                                <p>Please check your WhatsApp API settings and make sure the phone number is correct.</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
