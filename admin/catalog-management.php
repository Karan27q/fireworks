<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = sanitize_input($_POST['category_name']);
        $category_description = sanitize_input($_POST['category_description']);
        $parent_category = !empty($_POST['parent_category']) ? (int)$_POST['parent_category'] : NULL;
        $display_order = (int)$_POST['display_order'];
        
        // Handle category image upload
        $image_path = '';
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
            $upload_dir = '../assets/images/categories/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['category_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_file)) {
                $image_path = 'assets/images/categories/' . $file_name;
            } else {
                $error_message = "Failed to upload category image.";
            }
        }
        
        if (empty($error_message)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description, parent_id, image, display_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisi", $category_name, $category_description, $parent_category, $image_path, $display_order);
            
            if ($stmt->execute()) {
                $success_message = "Category added successfully!";
            } else {
                $error_message = "Error adding category: " . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_category'])) {
        $category_id = (int)$_POST['category_id'];
        $category_name = sanitize_input($_POST['category_name']);
        $category_description = sanitize_input($_POST['category_description']);
        $parent_category = !empty($_POST['parent_category']) ? (int)$_POST['parent_category'] : NULL;
        $display_order = (int)$_POST['display_order'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Handle category image upload
        $image_path = $_POST['existing_image'];
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
            $upload_dir = '../assets/images/categories/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['category_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_file)) {
                // Delete old image if it exists
                if (!empty($image_path) && file_exists('../' . $image_path)) {
                    unlink('../' . $image_path);
                }
                $image_path = 'assets/images/categories/' . $file_name;
            } else {
                $error_message = "Failed to upload category image.";
            }
        }
        
        if (empty($error_message)) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, parent_id = ?, image = ?, display_order = ?, is_featured = ? WHERE id = ?");
            $stmt->bind_param("ssissii", $category_name, $category_description, $parent_category, $image_path, $display_order, $is_featured, $category_id);
            
            if ($stmt->execute()) {
                $success_message = "Category updated successfully!";
            } else {
                $error_message = "Error updating category: " . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];
        
        // Check if category has products
        $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->bind_result($product_count);
        $stmt->fetch();
        $stmt->close();
        
        if ($product_count > 0) {
            $error_message = "Cannot delete category with associated products. Please reassign products first.";
        } else {
            // Get image path before deleting
            $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $stmt->bind_result($image_path);
            $stmt->fetch();
            $stmt->close();
            
            // Delete category
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $category_id);
            
            if ($stmt->execute()) {
                // Delete image file if it exists
                if (!empty($image_path) && file_exists('../' . $image_path)) {
                    unlink('../' . $image_path);
                }
                $success_message = "Category deleted successfully!";
            } else {
                $error_message = "Error deleting category: " . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['add_attribute'])) {
        $attribute_name = sanitize_input($_POST['attribute_name']);
        $attribute_type = sanitize_input($_POST['attribute_type']);
        
        $stmt = $conn->prepare("INSERT INTO product_attributes (name, type) VALUES (?, ?)");
        $stmt->bind_param("ss", $attribute_name, $attribute_type);
        
        if ($stmt->execute()) {
            $attribute_id = $conn->insert_id;
            
            // Add attribute values if applicable
            if ($attribute_type == 'select' || $attribute_type == 'radio' || $attribute_type == 'checkbox') {
                $values = explode(',', sanitize_input($_POST['attribute_values']));
                foreach ($values as $value) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $stmt_val = $conn->prepare("INSERT INTO attribute_values (attribute_id, value) VALUES (?, ?)");
                        $stmt_val->bind_param("is", $attribute_id, $value);
                        $stmt_val->execute();
                        $stmt_val->close();
                    }
                }
            }
            
            $success_message = "Product attribute added successfully!";
        } else {
            $error_message = "Error adding product attribute: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_attribute'])) {
        $attribute_id = (int)$_POST['attribute_id'];
        $attribute_name = sanitize_input($_POST['attribute_name']);
        $attribute_type = sanitize_input($_POST['attribute_type']);
        
        $stmt = $conn->prepare("UPDATE product_attributes SET name = ?, type = ? WHERE id = ?");
        $stmt->bind_param("ssi", $attribute_name, $attribute_type, $attribute_id);
        
        if ($stmt->execute()) {
            // Update attribute values if applicable
            if ($attribute_type == 'select' || $attribute_type == 'radio' || $attribute_type == 'checkbox') {
                // Delete existing values
                $stmt_del = $conn->prepare("DELETE FROM attribute_values WHERE attribute_id = ?");
                $stmt_del->bind_param("i", $attribute_id);
                $stmt_del->execute();
                $stmt_del->close();
                
                // Add new values
                $values = explode(',', sanitize_input($_POST['attribute_values']));
                foreach ($values as $value) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $stmt_val = $conn->prepare("INSERT INTO attribute_values (attribute_id, value) VALUES (?, ?)");
                        $stmt_val->bind_param("is", $attribute_id, $value);
                        $stmt_val->execute();
                        $stmt_val->close();
                    }
                }
            }
            
            $success_message = "Product attribute updated successfully!";
        } else {
            $error_message = "Error updating product attribute: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_attribute'])) {
        $attribute_id = (int)$_POST['attribute_id'];
        
        // Delete attribute values first
        $stmt = $conn->prepare("DELETE FROM attribute_values WHERE attribute_id = ?");
        $stmt->bind_param("i", $attribute_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete attribute
        $stmt = $conn->prepare("DELETE FROM product_attributes WHERE id = ?");
        $stmt->bind_param("i", $attribute_id);
        
        if ($stmt->execute()) {
            $success_message = "Product attribute deleted successfully!";
        } else {
            $error_message = "Error deleting product attribute: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['add_tag'])) {
        $tag_name = sanitize_input($_POST['tag_name']);
        
        $stmt = $conn->prepare("INSERT INTO product_tags (name) VALUES (?)");
        $stmt->bind_param("s", $tag_name);
        
        if ($stmt->execute()) {
            $success_message = "Product tag added successfully!";
        } else {
            $error_message = "Error adding product tag: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_tag'])) {
        $tag_id = (int)$_POST['tag_id'];
        $tag_name = sanitize_input($_POST['tag_name']);
        
        $stmt = $conn->prepare("UPDATE product_tags SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $tag_name, $tag_id);
        
        if ($stmt->execute()) {
            $success_message = "Product tag updated successfully!";
        } else {
            $error_message = "Error updating product tag: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_tag'])) {
        $tag_id = (int)$_POST['tag_id'];
        
        // Delete tag associations first
        $stmt = $conn->prepare("DELETE FROM product_tag_relationships WHERE tag_id = ?");
        $stmt->bind_param("i", $tag_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete tag
        $stmt = $conn->prepare("DELETE FROM product_tags WHERE id = ?");
        $stmt->bind_param("i", $tag_id);
        
        if ($stmt->execute()) {
            $success_message = "Product tag deleted successfully!";
        } else {
            $error_message = "Error deleting product tag: " . $conn->error;
        }
        $stmt->close();
    }
}

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
        $attribute_values[$row['attribute_id']][] = $row['value'];
    }
    $result->free();
}

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

// Function to display category tree
function displayCategoryTree($category_tree) {
    $html = '';
    foreach ($category_tree as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $category['level']);
        $html .= "<tr>
            <td>{$category['id']}</td>
            <td>{$indent}{$category['name']}</td>
            <td>" . ($category['parent_id'] ? getCategoryName($GLOBALS['categories'], $category['parent_id']) : 'None') . "</td>
            <td>{$category['display_order']}</td>
            <td>" . ($category['image'] ? "<img src='../{$category['image']}' width='50' height='50' alt='{$category['name']}'>" : 'No Image') . "</td>
            <td>
                <button type='button' class='btn btn-primary btn-sm' onclick='editCategory({$category['id']}, \"{$category['name']}\", \"{$category['description']}\", " . ($category['parent_id'] !== null ? $category['parent_id'] : 'null') . ", {$category['display_order']}, \"{$category['image']}\", {$category['is_featured']})'>Edit</button>
                <button type='button' class='btn btn-danger btn-sm' onclick='deleteCategory({$category['id']}, \"{$category['name']}\")'>Delete</button>
            </td>
        </tr>";
        
        if (!empty($category['children'])) {
            $html .= displayCategoryTree($category['children']);
        }
    }
    return $html;
}

// Function to get category name by ID
function getCategoryName($categories, $id) {
    foreach ($categories as $category) {
        if ($category['id'] == $id) {
            return $category['name'];
        }
    }
    return 'Unknown';
}

// Page title
$page_title = "Catalog Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
        .attribute-values-container {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" onclick="showTab('categories')">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" onclick="showTab('attributes')">Product Attributes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" onclick="showTab('tags')">Product Tags</a>
                    </li>
                </ul>
                
                <!-- Categories Tab -->
                <div id="categories" class="tab-content active">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Add New Category</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="category_name" class="form-label">Category Name</label>
                                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="parent_category" class="form-label">Parent Category</label>
                                        <select class="form-control" id="parent_category" name="parent_category">
                                            <option value="">None (Top Level)</option>
                                            <?php echo displayCategoryOptions($categories); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="display_order" class="form-label">Display Order</label>
                                        <input type="number" class="form-control" id="display_order" name="display_order" value="0" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="category_image" class="form-label">Category Image</label>
                                        <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="category_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="category_description" name="category_description" rows="3"></textarea>
                                </div>
                                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Categories</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Parent</th>
                                            <th>Display Order</th>
                                            <th>Image</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php echo displayCategoryTree($category_tree); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Attributes Tab -->
                <div id="attributes" class="tab-content">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Add New Product Attribute</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="attribute_name" class="form-label">Attribute Name</label>
                                        <input type="text" class="form-control" id="attribute_name" name="attribute_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="attribute_type" class="form-label">Attribute Type</label>
                                        <select class="form-control" id="attribute_type" name="attribute_type" onchange="toggleAttributeValues()">
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="select">Select (Dropdown)</option>
                                            <option value="radio">Radio Buttons</option>
                                            <option value="checkbox">Checkboxes</option>
                                            <option value="color">Color</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 attribute-values-container" id="attribute_values_container">
                                    <label for="attribute_values" class="form-label">Attribute Values (comma separated)</label>
                                    <textarea class="form-control" id="attribute_values" name="attribute_values" rows="3" placeholder="Red, Green, Blue"></textarea>
                                </div>
                                <button type="submit" name="add_attribute" class="btn btn-primary">Add Attribute</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Product Attributes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Values</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attributes as $attribute): ?>
                                            <tr>
                                                <td><?php echo $attribute['id']; ?></td>
                                                <td><?php echo $attribute['name']; ?></td>
                                                <td><?php echo $attribute['type']; ?></td>
                                                <td>
                                                    <?php 
                                                    if (isset($attribute_values[$attribute['id']])) {
                                                        echo implode(', ', $attribute_values[$attribute['id']]);
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm" onclick="editAttribute(<?php echo $attribute['id']; ?>, '<?php echo $attribute['name']; ?>', '<?php echo $attribute['type']; ?>', '<?php echo isset($attribute_values[$attribute['id']]) ? implode(', ', $attribute_values[$attribute['id']]) : ''; ?>')">Edit</button>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteAttribute(<?php echo $attribute['id']; ?>, '<?php echo $attribute['name']; ?>')">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Tags Tab -->
                <div id="tags" class="tab-content">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Add New Product Tag</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="tag_name" class="form-label">Tag Name</label>
                                    <input type="text" class="form-control" id="tag_name" name="tag_name" required>
                                </div>
                                <button type="submit" name="add_tag" class="btn btn-primary">Add Tag</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Product Tags</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tags as $tag): ?>
                                            <tr>
                                                <td><?php echo $tag['id']; ?></td>
                                                <td><?php echo $tag['name']; ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm" onclick="editTag(<?php echo $tag['id']; ?>, '<?php echo $tag['name']; ?>')">Edit</button>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteTag(<?php echo $tag['id']; ?>, '<?php echo $tag['name']; ?>')">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Category Modal -->
                <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="" method="post" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <input type="hidden" id="edit_category_id" name="category_id">
                                    <input type="hidden" id="existing_image" name="existing_image">
                                    <div class="mb-3">
                                        <label for="edit_category_name" class="form-label">Category Name</label>
                                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_parent_category" class="form-label">Parent Category</label>
                                        <select class="form-control" id="edit_parent_category" name="parent_category">
                                            <option value="">None (Top Level)</option>
                                            <?php echo displayCategoryOptions($categories); ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_display_order" class="form-label">Display Order</label>
                                        <input type="number" class="form-control" id="edit_display_order" name="display_order" value="0" min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_category_image" class="form-label">Category Image</label>
                                        <input type="file" class="form-control" id="edit_category_image" name="category_image" accept="image/*">
                                        <div id="current_image_preview" class="mt-2"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_category_description" class="form-label">Description</label>
                                        <textarea class="form-control" id="edit_category_description" name="category_description" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_is_featured" name="is_featured">
                                        <label class="form-check-label" for="edit_is_featured">Featured Category</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Category Modal -->
                <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteCategoryModalLabel">Delete Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete the category "<span id="delete_category_name"></span>"?</p>
                                <p class="text-danger">This action cannot be undone. All products in this category will need to be reassigned.</p>
                            </div>
                            <div class="modal-footer">
                                <form action="" method="post">
                                    <input type="hidden" id="delete_category_id" name="category_id">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="delete_category" class="btn btn-danger">Delete Category</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Attribute Modal -->
                <div class="modal fade" id="editAttributeModal" tabindex="-1" aria-labelledby="editAttributeModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editAttributeModalLabel">Edit Attribute</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="" method="post">
                                <div class="modal-body">
                                    <input type="hidden" id="edit_attribute_id" name="attribute_id">
                                    <div class="mb-3">
                                        <label for="edit_attribute_name" class="form-label">Attribute Name</label>
                                        <input type="text" class="form-control" id="edit_attribute_name" name="attribute_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_attribute_type" class="form-label">Attribute Type</label>
                                        <select class="form-control" id="edit_attribute_type" name="attribute_type" onchange="toggleEditAttributeValues()">
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="select">Select (Dropdown)</option>
                                            <option value="radio">Radio Buttons</option>
                                            <option value="checkbox">Checkboxes</option>
                                            <option value="color">Color</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 attribute-values-container" id="edit_attribute_values_container">
                                        <label for="edit_attribute_values" class="form-label">Attribute Values (comma separated)</label>
                                        <textarea class="form-control" id="edit_attribute_values" name="attribute_values" rows="3" placeholder="Red, Green, Blue"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_attribute" class="btn btn-primary">Update Attribute</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Attribute Modal -->
                <div class="modal fade" id="deleteAttributeModal" tabindex="-1" aria-labelledby="deleteAttributeModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteAttributeModalLabel">Delete Attribute</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete the attribute "<span id="delete_attribute_name"></span>"?</p>
                                <p class="text-danger">This action cannot be undone. All product associations with this attribute will be removed.</p>
                            </div>
                            <div class="modal-footer">
                                <form action="" method="post">
                                    <input type="hidden" id="delete_attribute_id" name="attribute_id">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="delete_attribute" class="btn btn-danger">Delete Attribute</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Tag Modal -->
                <div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editTagModalLabel">Edit Tag</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="" method="post">
                                <div class="modal-body">
                                    <input type="hidden" id="edit_tag_id" name="tag_id">
                                    <div class="mb-3">
                                        <label for="edit_tag_name" class="form-label">Tag Name</label>
                                        <input type="text" class="form-control" id="edit_tag_name" name="tag_name" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_tag" class="btn btn-primary">Update Tag</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Tag Modal -->
                <div class="modal fade" id="deleteTagModal" tabindex="-1" aria-labelledby="deleteTagModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteTagModalLabel">Delete Tag</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete the tag "<span id="delete_tag_name"></span>"?</p>
                                <p class="text-danger">This action cannot be undone. All product associations with this tag will be removed.</p>
                            </div>
                            <div class="modal-footer">
                                <form action="" method="post">
                                    <input type="hidden" id="delete_tag_id" name="tag_id">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="delete_tag" class="btn btn-danger">Delete Tag</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // Show tab function
        function showTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Update active tab link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            document.querySelector(`.nav-link[onclick="showTab('${tabId}')"]`).classList.add('active');
        }
        
        // Toggle attribute values container based on attribute type
        function toggleAttributeValues() {
            const attributeType = document.getElementById('attribute_type').value;
            const valuesContainer = document.getElementById('attribute_values_container');
            
            if (attributeType === 'select' || attributeType === 'radio' || attributeType === 'checkbox') {
                valuesContainer.style.display = 'block';
            } else {
                valuesContainer.style.display = 'none';
            }
        }
        
        // Toggle edit attribute values container based on attribute type
        function toggleEditAttributeValues() {
            const attributeType = document.getElementById('edit_attribute_type').value;
            const valuesContainer = document.getElementById('edit_attribute_values_container');
            
            if (attributeType === 'select' || attributeType === 'radio' || attributeType === 'checkbox') {
                valuesContainer.style.display = 'block';
            } else {
                valuesContainer.style.display = 'none';
            }
        }
        
        // Edit category function
        function editCategory(id, name, description, parent_id, display_order, image, is_featured) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_category_description').value = description;
            document.getElementById('edit_parent_category').value = parent_id !== null ? parent_id : '';
            document.getElementById('edit_display_order').value = display_order;
            document.getElementById('existing_image').value = image;
            document.getElementById('edit_is_featured').checked = is_featured === 1;
            
            // Show current image if exists
            const imagePreview = document.getElementById('current_image_preview');
            if (image) {
                imagePreview.innerHTML = `<img src="../${image}" width="100" height="100" alt="${name}">`;
            } else {
                imagePreview.innerHTML = 'No image currently set';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }
        
        // Delete category function
        function deleteCategory(id, name) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        }
        
        // Edit attribute function
        function editAttribute(id, name, type, values) {
            document.getElementById('edit_attribute_id').value = id;
            document.getElementById('edit_attribute_name').value = name;
            document.getElementById('edit_attribute_type').value = type;
            document.getElementById('edit_attribute_values').value = values;
            
            // Toggle values container based on type
            const valuesContainer = document.getElementById('edit_attribute_values_container');
            if (type === 'select' || type === 'radio' || type === 'checkbox') {
                valuesContainer.style.display = 'block';
            } else {
                valuesContainer.style.display = 'none';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editAttributeModal'));
            modal.show();
        }
        
        // Delete attribute function
        function deleteAttribute(id, name) {
            document.getElementById('delete_attribute_id').value = id;
            document.getElementById('delete_attribute_name').textContent = name;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('deleteAttributeModal'));
            modal.show();
        }
        
        // Edit tag function
        function editTag(id, name) {
            document.getElementById('edit_tag_id').value = id;
            document.getElementById('edit_tag_name').value = name;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editTagModal'));
            modal.show();
        }
        
        // Delete tag function
        function deleteTag(id, name) {
            document.getElementById('delete_tag_id').value = id;
            document.getElementById('delete_tag_name').textContent = name;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('deleteTagModal'));
            modal.show();
        }
        
        // Initialize attribute values container
        document.addEventListener('DOMContentLoaded', function() {
            toggleAttributeValues();
        });
    </script>
</body>
</html>
