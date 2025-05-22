<?php
/**
 * Product Import/Export Functions
 * 
 * This file contains functions for importing and exporting products in bulk.
 */

// Function to export products to CSV
function export_products_to_csv($category_id = null, $search = null) {
    global $conn;
    
    // Build query based on filters
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE 1=1";
    
    if ($category_id) {
        $query .= " AND p.category_id = " . intval($category_id);
    }
    
    if ($search) {
        $search = mysqli_real_escape_string($conn, $search);
        $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%' OR p.sku LIKE '%$search%')";
    }
    
    $query .= " ORDER BY p.id ASC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return false;
    }
    
    // Create a file pointer
    $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = '../uploads/exports/' . $filename;
    
    // Make sure the directory exists
    if (!file_exists('../uploads/exports/')) {
        mkdir('../uploads/exports/', 0755, true);
    }
    
    $f = fopen($filepath, 'w');
    
    // Set column headers
    $headers = [
        'ID', 'SKU', 'Name', 'Description', 'Short Description', 'Price', 'Sale Price', 
        'Stock Quantity', 'Category', 'Image', 'Weight', 'Dimensions', 'Active', 
        'Featured', 'Meta Title', 'Meta Description', 'Meta Keywords'
    ];
    
    fputcsv($f, $headers);
    
    // Output each row of the data
    while ($row = mysqli_fetch_assoc($result)) {
        $line = [
            $row['id'],
            $row['sku'],
            $row['name'],
            $row['description'],
            $row['short_description'],
            $row['price'],
            $row['sale_price'],
            $row['stock_quantity'],
            $row['category_name'],
            $row['image'],
            $row['weight'],
            $row['dimensions'],
            $row['active'],
            $row['featured'],
            $row['meta_title'],
            $row['meta_description'],
            $row['meta_keywords']
        ];
        
        fputcsv($f, $line);
    }
    
    // Close the file
    fclose($f);
    
    // Log the export
    $admin_id = $_SESSION['admin_id'];
    $log_query = "INSERT INTO activity_log (user_id, user_type, action, details, ip_address) 
                 VALUES ($admin_id, 'admin', 'export_products', 'Exported " . mysqli_num_rows($result) . " products to CSV', '{$_SERVER['REMOTE_ADDR']}')";
    mysqli_query($conn, $log_query);
    
    return [
        'filename' => $filename,
        'filepath' => $filepath,
        'count' => mysqli_num_rows($result)
    ];
}

// Function to import products from CSV
function import_products_from_csv($file, $column_mapping, $update_existing = false) {
    global $conn;
    
    // Check if file exists
    if (!file_exists($file)) {
        return [
            'success' => false,
            'message' => 'File not found'
        ];
    }
    
    // Open the file
    $f = fopen($file, 'r');
    
    // Skip the header row
    fgetcsv($f);
    
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    
    // Process each row
    while (($line = fgetcsv($f)) !== false) {
        $data = [];
        
        // Map columns based on the provided mapping
        foreach ($column_mapping as $db_column => $csv_index) {
            if (isset($line[$csv_index])) {
                $data[$db_column] = $line[$csv_index];
            }
        }
        
        // Validate required fields
        if (empty($data['name']) || !isset($data['price']) || $data['price'] === '') {
            $skipped++;
            $errors[] = "Row skipped: Missing required fields (name or price)";
            continue;
        }
        
        // Check if product exists (by SKU or ID)
        $exists = false;
        $existing_id = null;
        
        if (!empty($data['sku'])) {
            $check_query = "SELECT id FROM products WHERE sku = '" . mysqli_real_escape_string($conn, $data['sku']) . "'";
            $check_result = mysqli_query($conn, $check_query);
            
            if ($check_result && mysqli_num_rows($check_result) > 0) {
                $exists = true;
                $existing_id = mysqli_fetch_assoc($check_result)['id'];
            }
        } elseif (!empty($data['id'])) {
            $check_query = "SELECT id FROM products WHERE id = " . intval($data['id']);
            $check_result = mysqli_query($conn, $check_query);
            
            if ($check_result && mysqli_num_rows($check_result) > 0) {
                $exists = true;
                $existing_id = intval($data['id']);
            }
        }
        
        // Handle category
        if (!empty($data['category_name'])) {
            $category_query = "SELECT id FROM categories WHERE name = '" . mysqli_real_escape_string($conn, $data['category_name']) . "'";
            $category_result = mysqli_query($conn, $category_query);
            
            if ($category_result && mysqli_num_rows($category_result) > 0) {
                $data['category_id'] = mysqli_fetch_assoc($category_result)['id'];
            } else {
                // Create new category
                $insert_category = "INSERT INTO categories (name, slug, active) VALUES (
                                   '" . mysqli_real_escape_string($conn, $data['category_name']) . "',
                                   '" . mysqli_real_escape_string($conn, create_slug($data['category_name'])) . "',
                                   1)";
                mysqli_query($conn, $insert_category);
                $data['category_id'] = mysqli_insert_id($conn);
            }
            
            // Remove category_name from data
            unset($data['category_name']);
        }
        
        // If product exists and update_existing is false, skip
        if ($exists && !$update_existing) {
            $skipped++;
            continue;
        }
        
        // Prepare data for SQL
        $sql_data = [];
        foreach ($data as $key => $value) {
            if ($key != 'id') { // Don't include ID in the update/insert data
                $sql_data[] = "$key = '" . mysqli_real_escape_string($conn, $value) . "'";
            }
        }
        
        // Update or insert
        if ($exists && $update_existing) {
            $update_query = "UPDATE products SET " . implode(', ', $sql_data) . " WHERE id = $existing_id";
            
            if (mysqli_query($conn, $update_query)) {
                $updated++;
            } else {
                $errors[] = "Error updating product: " . mysqli_error($conn);
                $skipped++;
            }
        } else {
            $insert_query = "INSERT INTO products SET " . implode(', ', $sql_data);
            
            if (mysqli_query($conn, $insert_query)) {
                $imported++;
            } else {
                $errors[] = "Error inserting product: " . mysqli_error($conn);
                $skipped++;
            }
        }
    }
    
    // Close the file
    fclose($f);
    
    // Log the import
    $admin_id = $_SESSION['admin_id'];
    $log_query = "INSERT INTO activity_log (user_id, user_type, action, details, ip_address) 
                 VALUES ($admin_id, 'admin', 'import_products', 'Imported $imported products, updated $updated, skipped $skipped', '{$_SERVER['REMOTE_ADDR']}')";
    mysqli_query($conn, $log_query);
    
    return [
        'success' => true,
        'imported' => $imported,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

// Helper function to create slug
function create_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', ' ', $string);
    $string = preg_replace('/\s/', '-', $string);
    return $string;
}

// Function to get CSV column headers
function get_csv_headers($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    $f = fopen($file, 'r');
    $headers = fgetcsv($f);
    fclose($f);
    
    return $headers;
}

// Function to export products to Excel
function export_products_to_excel($category_id = null, $search = null) {
    // For Excel export, we'll use the CSV export and then convert it
    $csv_result = export_products_to_csv($category_id, $search);
    
    if (!$csv_result) {
        return false;
    }
    
    // Change file extension
    $excel_filename = str_replace('.csv', '.xlsx', $csv_result['filename']);
    $excel_filepath = str_replace('.csv', '.xlsx', $csv_result['filepath']);
    
    // In a real implementation, you would use a PHP Excel library like PhpSpreadsheet
    // For this example, we'll just rename the file
    copy($csv_result['filepath'], $excel_filepath);
    
    return [
        'filename' => $excel_filename,
        'filepath' => $excel_filepath,
        'count' => $csv_result['count']
    ];
}
