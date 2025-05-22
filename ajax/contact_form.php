<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => 'An error occurred. Please try again.'
];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Insert message into database
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$name, $email, $subject, $message]);
        
        if ($result) {
            // Send email notification to admin (optional)
            $admin_email = 'admin@fireworks-ecommerce.com'; // Replace with actual admin email
            $email_subject = "New Contact Form Submission: $subject";
            $email_body = "Name: $name\nEmail: $email\nSubject: $subject\nMessage: $message";
            $headers = "From: $email";
            
            // Uncomment to enable email sending
            // mail($admin_email, $email_subject, $email_body, $headers);
            
            $response['success'] = true;
            $response['message'] = 'Your message has been sent successfully. We will get back to you soon!';
        }
    } catch (PDOException $e) {
        error_log("Contact form error: " . $e->getMessage());
        $response['message'] = 'Database error. Please try again later.';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
