<?php
session_start();

// Define admin path constant
define('ADMIN_PATH', true);

// Include database connection
include '../includes/db_connect.php';
include '../includes/product_import_export.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';
$import_result = null;
$export_result = null;
$csv_headers = null;
$db_columns = [
    'id' => 'ID',
    'sku' => 'SKU',
    'name' => 'Name',
    'description' => 'Description',
    'short_description' => 'Short Description',
    'price' => 'Price',
    'sale_price' => 'Sale Price',
    'stock_quantity' => 'Stock Quantity',
    'category_id' => 'Category ID',
    'category_name' => 'Category Name',
    'image' => 'Image',
    'weight' => 'Weight',
    'dimensions' => 'Dimensions',
    'active' => 'Active',
    'featured' => 'Featured',
    'meta_title' => 'Meta Title',
    'meta_description' => 'Meta Description',
    'meta_keywords' => 'Meta Keywords'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Export products
    if (isset($_POST['export'])) {
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $search = isset($_POST['search']) ? $_POST['search'] : null;
        $format = isset($_POST['export_format']) ? $_POST['export_format'] : 'csv';
        
        if ($format === 'csv') {
            $export_result = export_products_to_csv($category_id, $search);
        } else {
            $export_result = export_products_to_excel($category_id, $search);
        }
        
        if ($export_result) {
            $message = "Successfully exported {$export_result['count']} products to {$format}.";
        } else {
            $error = "Failed to export products.";
        }
    }
    
    // Import products - Step 1: Upload file
    if (isset($_POST['upload_import_file'])) {
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
            $file_tmp = $_FILES['import_file']['tmp_name'];
            $file_name = $_FILES['import_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Check file extension
            if ($file_ext != 'csv' && $file_ext != 'xlsx') {
                $error = "Only CSV and Excel files are allowed.";
            } else {
                // Create upload directory if it doesn't exist
                if (!file_exists('../uploads/imports/')) {
                    mkdir('../uploads/imports/', 0755, true);
                }
                
                $upload_path = '../uploads/imports/' . time() . '_' . $file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // If Excel file, convert to CSV (in a real implementation)
                    if ($file_ext == 'xlsx') {
                        // This would use a PHP Excel library
                        $error = "Excel import is not implemented in this demo.";
                    } else {
                        // Get CSV headers
                        $csv_headers = get_csv_headers($upload_path);
                        
                        if ($csv_headers) {
                            $_SESSION['import_file_path'] = $upload_path;
                            $message = "File uploaded successfully. Please map the columns.";
                        } else {
                            $error = "Failed to read CSV headers.";
                        }
                    }
                } else {
                    $error = "Failed to upload file.";
                }
            }
        } else {
            $error = "Please select a file to import.";
        }
    }
    
    // Import products - Step 2: Process import
    if (isset($_POST['process_import'])) {
        if (isset($_SESSION['import_file_path']) && file_exists($_SESSION['import_file_path'])) {
            // Build column mapping
            $column_mapping = [];
            foreach ($_POST['column_map'] as $db_column => $csv_index) {
                if ($csv_index != '') {
                    $column_mapping[$db_column] = intval($csv_index);
                }
            }
            
            // Check if required fields are mapped
            if (!isset($column_mapping['name']) || !isset($column_mapping['price'])) {
                $error = "Name and Price columns must be mapped.";
            } else {
                $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] == '1';
                
                $import_result = import_products_from_csv($_SESSION['import_file_path'], $column_mapping, $update_existing);
                
                if ($import_result['success']) {
                    $message = "Import completed: {$import_result['imported']} products imported, {$import_result['updated']} updated, {$import_result['skipped']} skipped.";
                    
                    // Clear the session variable
                    unset($_SESSION['import_file_path']);
                    $csv_headers = null;
                } else {
                    $error = "Import failed: " . $import_result['message'];
                }
            }
        } else {
            $error = "Import file not found. Please upload again.";
        }
    }
}

// Get categories for filter
$categoriesQuery = "SELECT * FROM categories WHERE active = 1 ORDER BY name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
$categories = mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Import/Export - Fireworks Shop</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .import-steps {
            display: flex;
            margin-bottom: 20px;
        }
        
        .import-step {
            flex: 1;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin-right: 10px;
            position: relative;
        }
        
        .import-step:last-child {
            margin-right: 0;
        }
        
        .import-step:after {
            content: '\f054';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: -7px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .import-step:last-child:after {
            display: none;
        }
        
        .import-step.active {
            background-color: #e8f5e9;
            border-left: 3px solid #4caf50;
        }
        
        .import-step h3 {
            margin-top: 0;
            color: #333;
        }
        
        .column-mapping {
            margin-bottom: 20px;
        }
        
        .column-mapping-row {
            display: flex;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .column-mapping-label {
            width: 200px;
            font-weight: bold;
        }
        
        .required-field {
            color: #f44336;
        }
        
        .import-results {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
        
        .import-results h3 {
            margin-top: 0;
        }
        
        .import-errors {
            margin-top: 10px;
            color: #f44336;
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
                    <h1>Bulk Import/Export</h1>
                    <div class="actions">
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
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
                    <div class="card">
                        <div class="card-header">
                            <h2>Export Products</h2>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="category_id">Category</label>
                                        <select name="category_id" id="category_id" class="form-control">
                                            <option value="">All Categories</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo $category['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group col-md-4">
                                        <label for="search">Search</label>
                                        <input type="text" name="search" id="search" class="form-control" placeholder="Search products...">
                                    </div>
                                    
                                    <div class="form-group col-md-4">
                                        <label for="export_format">Format</label>
                                        <select name="export_format" id="export_format" class="form-control">
                                            <option value="csv">CSV</option>
                                            <option value="excel">Excel</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" name="export" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Export Products
                                    </button>
                                </div>
                            </form>
                            
                            <?php if($export_result): ?>
                                <div class="alert alert-info">
                                    <p>Export completed successfully. <?php echo $export_result['count']; ?> products exported.</p>
                                    <p>
                                        <a href="<?php echo str_replace('../', '', $export_result['filepath']); ?>" class="btn btn-sm btn-primary" download>
                                            <i class="fas fa-download"></i> Download File
                                        </a>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h2>Import Products</h2>
                        </div>
                        <div class="card-body">
                            <div class="import-steps">
                                <div class="import-step <?php echo !$csv_headers ? 'active' : ''; ?>">
                                    <h3><i class="fas fa-upload"></i> Step 1: Upload File</h3>
                                    <p>Upload a CSV or Excel file with product data.</p>
                                </div>
                                
                                <div class="import-step <?php echo $csv_headers && !$import_result ? 'active' : ''; ?>">
                                    <h3><i class="fas fa-columns"></i> Step 2: Map Columns</h3>
                                    <p>Map CSV columns to product fields.</p>
                                </div>
                                
                                <div class="import-step <?php echo $import_result ? 'active' : ''; ?>">
                                    <h3><i class="fas fa-check-circle"></i> Step 3: Import</h3>
                                    <p>Process the import and view results.</p>
                                </div>
                            </div>
                            
                            <?php if(!$csv_headers): ?>
                                <!-- Step 1: Upload File -->
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="import_file">Select File</label>
                                        <input type="file" name="import_file" id="import_file" class="form-control" required>
                                        <small class="form-text text-muted">Supported formats: CSV, Excel (.xlsx)</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" name="upload_import_file" class="btn btn-primary">
                                            <i class="fas fa-upload"></i> Upload File
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="alert alert-info">
                                    <h4>Import Instructions</h4>
                                    <p>To import products, follow these steps:</p>
                                    <ol>
                                        <li>Prepare a CSV or Excel file with product data.</li>
                                        <li>Upload the file using the form above.</li>
                                        <li>Map the columns in your file to the product fields.</li>
                                        <li>Click "Import Products" to start the import process.</li>
                                    </ol>
                                    <p>Required fields: Name, Price</p>
                                </div>
                            <?php elseif(!$import_result): ?>
                                <!-- Step 2: Map Columns -->
                                <form action="" method="POST">
                                    <div class="column-mapping">
                                        <h3>Column Mapping</h3>
                                        <p>Map the columns in your CSV file to the product fields.</p>
                                        
                                        <?php foreach($db_columns as $column => $label): ?>
                                            <div class="column-mapping-row">
                                                <div class="column-mapping-label">
                                                    <?php echo $label; ?>
                                                    <?php if($column == 'name' || $column == 'price'): ?>
                                                        <span class="required-field">*</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="column-mapping-select">
                                                    <select name="column_map[<?php echo $column; ?>]" class="form-control">
                                                        <option value="">-- Not Mapped --</option>
                                                        <?php foreach($csv_headers as $index => $header): ?>
                                                            <option value="<?php echo $index; ?>" <?php echo (strtolower($header) == strtolower($label)) ? 'selected' : ''; ?>>
                                                                <?php echo $header; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="update_existing" name="update_existing" value="1" checked>
                                            <label class="custom-control-label" for="update_existing">Update existing products (if SKU or ID matches)</label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" name="process_import" class="btn btn-primary">
                                            <i class="fas fa-file-import"></i> Import Products
                                        </button>
                                        <a href="bulk-import-export.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <!-- Step 3: Import Results -->
                                <div class="import-results">
                                    <h3>Import Results</h3>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="stat-card">
                                                <div class="stat-card-icon">
                                                    <i class="fas fa-plus-circle"></i>
                                                </div>
                                                <div class="stat-card-info">
                                                    <h3>Imported</h3>
                                                    <p><?php echo $import_result['imported']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="stat-card">
                                                <div class="stat-card-icon">
                                                    <i class="fas fa-edit"></i>
                                                </div>
                                                <div class="stat-card-info">
                                                    <h3>Updated</h3>
                                                    <p><?php echo $import_result['updated']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="stat-card">
                                                <div class="stat-card-icon">
                                                    <i class="fas fa-ban"></i>
                                                </div>
                                                <div class="stat-card-info">
                                                    <h3>Skipped</h3>
                                                    <p><?php echo $import_result['skipped']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if(!empty($import_result['errors'])): ?>
                                        <div class="import-errors">
                                            <h4>Errors</h4>
                                            <ul>
                                                <?php foreach($import_result['errors'] as $error): ?>
                                                    <li><?php echo $error; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-group mt-4">
                                        <a href="products.php" class="btn btn-primary">
                                            <i class="fas fa-list"></i> View Products
                                        </a>
                                        <a href="bulk-import-export.php" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i> Import More Products
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
