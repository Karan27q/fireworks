<?php
// Include database connection
include '../../includes/db_connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'ID is required']);
    exit;
}

$id = (int)$_GET['id'];

// Get payment detail
$query = "SELECT * FROM payment_details WHERE id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['error' => 'Payment detail not found']);
    exit;
}

$paymentDetail = mysqli_fetch_assoc($result);

// Return payment detail as JSON
header('Content-Type: application/json');
echo json_encode($paymentDetail);
