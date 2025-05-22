<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to add items to cart'
    ]);
    exit;
}

// Check if product_id and quantity are set
if(!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$productId = $_POST['product_id'];
$quantity = (int)$_POST['quantity'];

// Validate quantity
if($quantity <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Quantity must be greater than zero'
    ]);
    exit;
}

// Check if product exists and is active
$productQuery = "SELECT * FROM products WHERE id = $productId AND active = 1";
$productResult = mysqli_query($conn, $productQuery);

if(mysqli_num_rows($productResult) == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found or unavailable'
    ]);
    exit;
}

$product = mysqli_fetch_assoc($productResult);

// Check if product is in stock
if($product['stock_quantity'] < $quantity) {
    echo json_encode([
        'success' => false,
        'message' => 'Not enough stock available'
    ]);
    exit;
}

// Check if product is already in cart
$checkCartQuery = "SELECT * FROM cart WHERE user_id = $userId AND product_id = $productId";
$checkCartResult = mysqli_query($conn, $checkCartQuery);

if(mysqli_num_rows($checkCartResult) > 0) {
    // Update quantity
    $cartItem = mysqli_fetch_assoc($checkCartResult);
    $newQuantity = $cartItem['quantity'] + $quantity;
    
    // Check if new quantity exceeds stock
    if($newQuantity > $product['stock_quantity']) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot add more of this item due to stock limitations'
        ]);
        exit;
    }
    
    $updateQuery = "UPDATE cart SET quantity = $newQuantity WHERE id = {$cartItem['id']}";
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update cart'
        ]);
    }
} else {
    // Add new item to cart
    $insertQuery = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, $quantity)";
    $insertResult = mysqli_query($conn, $insertQuery);
    
    if($insertResult) {
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add product to cart'
        ]);
    }
}
