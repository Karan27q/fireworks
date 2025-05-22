<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if product exists
$product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    header("Location: products.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_attributes'])) {
        // Delete existing product attribute values
        $stmt = $conn->prepare("DELETE FROM product_attribute_values WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert new attribute values
        foreach ($_POST['attribute'] as $attribute_id => $value) {
            if (!empty($value)) {
                $stmt = $conn->prepare("INSERT INTO product_attribute_values (product_id, attribute_id, value) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $product_id, $attribute_id, $value);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        $success_message = "Product attributes updated successfully!";
    } elseif (isset($_POST['update_tags'])) {
        // Delete existing product tag relationships
        $stmt = $conn->prepare("DELETE FROM product_tag_relationships WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert new tag relationships
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            foreach ($_POST['tags'] as $tag_id) {
                $stmt = $conn->prepare("INSERT INTO product_tag_relationships (product_id, tag_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $product_id, $tag_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        $success_message = "Product tags updated successfully!";
    } elseif (isset($_POST['update_category'])) {
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
        
        $stmt = $conn->prepare("UPDATE products SET category_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $category_id, $product_id);
        
        if ($stmt->execute()) {
            $success_message = "Product category updated successfully!";
        } else {
            $error_message = "Error updating product category: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_related'])) {
        // Delete existing related products
        $stmt = $conn->prepare("DELETE FROM related_products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert new related products
        if (isset($_POST['related_products']) && is_array($_POST['related_products'])) {
            foreach ($_POST['related_products'] as $related_id) {
                $stmt = $conn->prepare("INSERT INTO related_products (product_id, related_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $product_id, $related_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        $success_message = "Related products updated successfully!";
    } elseif (isset($_POST['update_seo'])) {
        $meta_title = sanitize_input($_POST['meta_title']);
        $meta_description = sanitize_input($_POST['meta_description']);
        $meta_keywords = sanitize_input($_POST['meta_keywords']);
        $slug = sanitize_input($_POST['slug']);
        
        // If slug is empty, generate from name
        if (empty($slug)) {
            $slug = create_slug($product['name']);
        }
        
        // Check if slug exists for another product
        $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $slug, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error_message = "URL slug already exists. Please choose a different one.";
        } else {
            $stmt = $conn->prepare("UPDATE products SET meta_title = ?, meta_description = ?, meta_keywords = ?, slug = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $meta_title, $meta_description, $meta_keywords, $slug, $product_id);
            
            if ($stmt->execute()) {
                $success_message = "Product SEO information updated successfully!";
                // Update product variable with new values
                $product['meta_title'] = $meta_title;
                $product['meta_description'] = $meta_description;
                $product['meta_keywords'] = $meta_keywords;
                $product['slug'] = $slug;
            } else {
                $error_message = "Error updating product SEO information: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Get all attributes
$attributes = [];
$query = "SELECT * FROM product_attributes ORDER BY name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $attributes[] = $row;
    }
    $result->free();
}

// Get attribute values
$attribute_values = [];
$query = "SELECT * FROM attribute_values ORDER BY attribute_id, value";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!isset($attribute_values[$row['attribute_id']])) {
            $attribute_values[$row['attribute_id']] = [];
        }
        $attribute_values[$row['attribute_id']][] = $row;
    }
    $result->free();
}

// Get product attribute values
$product_attribute_values = [];
$stmt = $conn->prepare("SELECT * FROM product_attribute_values WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $product_attribute_values[$row['attribute_id']] = $row['value'];
    }
    $result->free();
}
$stmt->close();

// Get all tags
$tags = [];
$query = "SELECT * FROM product_tags ORDER BY name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    $result->free();
}

// Get product tags
$product_tags = [];
$stmt = $conn->prepare("SELECT tag_id FROM product_tag_relationships WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $product_tags[] = $row['tag_id'];
    }
    $result->free();
}
$stmt->close();

// Get all categories
$categories = [];
$query = "SELECT * FROM categories ORDER BY display_order, name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $result->free();
}

// Get all products for related products selection
$all_products = [];
$stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id != ? ORDER BY name");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_products[] = $row;
    }
    $result->free();
}
$stmt->close();

// Get related products
$related_products = [];
$stmt = $conn->prepare("SELECT related_id FROM related_products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $related_products[] = $row['related_id'];
    }
    $result->free();
}
$stmt->close();

// Function to build category tree
function buildCategoryTree($categories, $parent_id = NULL, $level = 0) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parent_id) {
            $category['level'] = $level;
            $category['children'] = buildCategoryTree($categories, $category['id'], $level + 1);
            $tree[] = $category;
        }
    }
    return $tree;
}

// Build category tree
$category_tree = buildCategoryTree($categories);

// Function to display category options
function displayCategoryOptions($categories, $selected_id = NULL, $parent_id = NULL, $level = 0) {
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
    $html = '';
    
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parent_id) {
            $selected = ($selected_id == $category['id']) ? 'selected' : '';
            $html .= "<option value='{$category['id']}' {$selected}>{$indent}{$category['name']}</option>";
            $html .= displayCategoryOptions($categories, $selected_id, $category['id'], $level + 1);
        }
    }
    
    return $html;
}

// Function to create slug
function create_slug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Page title
$page_title = "Advanced Product Settings: " . $product['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .nav-tabs {
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link {
            cursor: pointer;
            padding: 10px 15px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            margin-right: 5px;
            border-radius: 4px 4px 0 0;
        }
        .nav-tabs .nav-link.active {
            background-color: #fff;
            border-bottom-color: #fff;
        }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    <div class
