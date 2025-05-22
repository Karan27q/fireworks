<?php
// Set page title
$pageTitle = "Order Payment";

// Include database connection
include 'includes/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// Check if order ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my-orders.php');
    exit;
}

$orderId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Get order details
$orderQuery = "SELECT * FROM orders WHERE id = $orderId AND user_id = $userId";
$orderResult = mysqli_query($conn, $orderQuery);

// Check if order exists and belongs to the user
if(mysqli_num_rows($orderResult) === 0) {
    header('Location: my-orders.php');
    exit;
}

$order = mysqli_fetch_assoc($orderResult);

// Check if order is already paid
if($order['payment_status'] === 'completed') {
    header('Location: order-success.php?id=' . $orderId);
    exit;
}

// Get payment methods
$paymentQuery = "SELECT * FROM payment_details WHERE active = 1 ORDER BY display_order ASC";
$paymentResult = mysqli_query($conn, $paymentQuery);

// Process payment confirmation
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $paymentMethod = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transactionId = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $paymentDate = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $paymentAmount = mysqli_real_escape_string($conn, $_POST['payment_amount']);
    $paymentNotes = mysqli_real_escape_string($conn, $_POST['payment_notes']);
    
    // Check if payment screenshot is uploaded
    $screenshotPath = '';
    if(isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['payment_screenshot']['type'];
        
        if(in_array($fileType, $allowedTypes)) {
            $fileName = time() . '_' . $_FILES['payment_screenshot']['name'];
            $uploadDir = 'uploads/payment_proof/';
            
            // Create directory if it doesn't exist
            if(!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $uploadPath = $uploadDir . $fileName;
            
            if(move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $uploadPath)) {
                $screenshotPath = $fileName;
            } else {
                $error = "Failed to upload payment screenshot. Please try again.";
            }
        } else {
            $error = "Invalid file type. Please upload a JPEG or PNG image.";
        }
    }
    
    if(empty($error)) {
        // Update order with payment information
        $updateQuery = "UPDATE orders SET 
                        payment_method = '$paymentMethod',
                        payment_transaction_id = '$transactionId',
                        payment_date = '$paymentDate',
                        payment_screenshot = '$screenshotPath',
                        payment_notes = '$paymentNotes',
                        payment_status = 'pending_verification',
                        updated_at = NOW()
                        WHERE id = $orderId AND user_id = $userId";
        
        $updateResult = mysqli_query($conn, $updateQuery);
        
        if($updateResult) {
            // Send notification to admin
            $adminNotificationQuery = "INSERT INTO admin_notifications (type, message, reference_id, created_at)
                                      VALUES ('payment_confirmation', 'Payment confirmation received for Order #$orderId', $orderId, NOW())";
            mysqli_query($conn, $adminNotificationQuery);
            
            // Redirect to success page
            header('Location: order-success.php?id=' . $orderId . '&payment=confirmed');
            exit;
        } else {
            $error = "Failed to update payment information. Please try again.";
        }
    }
}

// Include header
include 'includes/header.php';

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}
?>

<div class="container">
    <div class="page-header">
        <h1>Order Payment</h1>
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="my-orders.php">My Orders</a> &gt; 
            <span>Order Payment</span>
        </div>
    </div>
    
    <?php if($error): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <?php if($message): ?>
    <div class="alert alert-success">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <div class="order-payment-container <?php echo $isMobile ? 'mobile-view' : ''; ?>">
        <div class="order-summary-card">
            <div class="card-header">
                <h2>Order Summary</h2>
                <span class="order-id">Order #<?php echo $orderId; ?></span>
            </div>
            <div class="card-body">
                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Status:</span>
                        <span class="detail-value status-badge <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status:</span>
                        <span class="detail-value status-badge <?php echo $order['payment_status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['payment_status'])); ?></span>
                    </div>
                </div>
                
                <div class="order-amount">
                    <div class="amount-row">
                        <span class="amount-label">Subtotal:</span>
                        <span class="amount-value">₹<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <?php if($order['discount_amount'] > 0): ?>
                    <div class="amount-row discount">
                        <span class="amount-label">Discount:</span>
                        <span class="amount-value">-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="amount-row">
                        <span class="amount-label">Shipping:</span>
                        <span class="amount-value">₹<?php echo number_format($order['shipping_amount'], 2); ?></span>
                    </div>
                    <div class="amount-row">
                        <span class="amount-label">Tax:</span>
                        <span class="amount-value">₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="amount-row total">
                        <span class="amount-label">Total Amount:</span>
                        <span class="amount-value">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="payment-options-container">
            <h2>Payment Options</h2>
            <p class="payment-instruction">Please select a payment method below and complete your payment. After making the payment, fill out the payment confirmation form.</p>
            
            <div class="payment-methods-tabs">
                <div class="tabs-header">
                    <?php 
                    mysqli_data_seek($paymentResult, 0);
                    $firstTab = true;
                    while($payment = mysqli_fetch_assoc($paymentResult)): 
                    ?>
                    <button class="tab-btn <?php echo $firstTab ? 'active' : ''; ?>" data-tab="payment-<?php echo $payment['id']; ?>">
                        <?php echo $payment['title']; ?>
                    </button>
                    <?php 
                    $firstTab = false;
                    endwhile; 
                    ?>
                </div>
                
                <div class="tabs-content">
                    <?php 
                    mysqli_data_seek($paymentResult, 0);
                    $firstTab = true;
                    while($payment = mysqli_fetch_assoc($paymentResult)): 
                    ?>
                    <div class="tab-pane <?php echo $firstTab ? 'active' : ''; ?>" id="payment-<?php echo $payment['id']; ?>">
                        <div class="payment-method-description">
                            <?php echo $payment['description']; ?>
                        </div>
                        
                        <?php if($payment['payment_type'] === 'bank'): ?>
                        <div class="payment-details bank-details">
                            <h3>Bank Account Details</h3>
                            <table class="payment-details-table">
                                <tr>
                                    <th>Account Name</th>
                                    <td><?php echo $payment['account_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Account Number</th>
                                    <td>
                                        <div class="copyable-field">
                                            <span id="account-number-<?php echo $payment['id']; ?>"><?php echo $payment['account_number']; ?></span>
                                            <button class="copy-btn" data-clipboard-target="#account-number-<?php echo $payment['id']; ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>IFSC Code</th>
                                    <td>
                                        <div class="copyable-field">
                                            <span id="ifsc-code-<?php echo $payment['id']; ?>"><?php echo $payment['ifsc_code']; ?></span>
                                            <button class="copy-btn" data-clipboard-target="#ifsc-code-<?php echo $payment['id']; ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td><?php echo $payment['bank_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Branch</th>
                                    <td><?php echo $payment['branch_name']; ?></td>
                                </tr>
                            </table>
                            
                            <div class="payment-note">
                                <p><strong>Important:</strong> Please include your Order #<?php echo $orderId; ?> in the payment reference/description.</p>
                            </div>
                        </div>
                        <?php elseif($payment['payment_type'] === 'upi'): ?>
                        <div class="payment-details upi-details">
                            <h3>UPI Payment Details</h3>
                            
                            <div class="upi-id-container">
                                <p><strong>UPI ID:</strong></p>
                                <div class="copyable-field">
                                    <span id="upi-id-<?php echo $payment['id']; ?>"><?php echo $payment['upi_id']; ?></span>
                                    <button class="copy-btn" data-clipboard-target="#upi-id-<?php echo $payment['id']; ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if(!empty($payment['qr_code_image'])): ?>
                            <div class="qr-code-container">
                                <p>Scan QR Code to Pay:</p>
                                <img src="uploads/payment/<?php echo $payment['qr_code_image']; ?>" alt="QR Code" class="qr-code-image">
                                <a href="uploads/payment/<?php echo $payment['qr_code_image']; ?>" download="Vamsi_Crackers_QR_<?php echo $payment['id']; ?>.jpg" class="download-qr-btn">
                                    <i class="fas fa-download"></i> Download QR Code
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="payment-note">
                                <p><strong>Important:</strong> Please add "Order #<?php echo $orderId; ?>" in the payment note/description.</p>
                            </div>
                            
                            <div class="upi-apps">
                                <p>Pay using:</p>
                                <div class="upi-app-buttons">
                                    <a href="upi://pay?pa=<?php echo $payment['upi_id']; ?>&pn=Vamsi%20Crackers&am=<?php echo $order['total_amount']; ?>&cu=INR&tn=Order%20%23<?php echo $orderId; ?>" class="upi-app-btn">
                                        <i class="fas fa-mobile-alt"></i> Any UPI App
                                    </a>
                                    <a href="phonepe://pay?pa=<?php echo $payment['upi_id']; ?>&pn=Vamsi%20Crackers&am=<?php echo $order['total_amount']; ?>&cu=INR&tn=Order%20%23<?php echo $orderId; ?>" class="upi-app-btn phonepe">
                                        <i class="fab fa-google-pay"></i> PhonePe
                                    </a>
                                    <a href="gpay://pay?pa=<?php echo $payment['upi_id']; ?>&pn=Vamsi%20Crackers&am=<?php echo $order['total_amount']; ?>&cu=INR&tn=Order%20%23<?php echo $orderId; ?>" class="upi-app-btn gpay">
                                        <i class="fab fa-google-pay"></i> Google Pay
                                    </a>
                                    <a href="paytm://pay?pa=<?php echo $payment['upi_id']; ?>&pn=Vamsi%20Crackers&am=<?php echo $order['total_amount']; ?>&cu=INR&tn=Order%20%23<?php echo $orderId; ?>" class="upi-app-btn paytm">
                                        <i class="fas fa-wallet"></i> Paytm
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                    $firstTab = false;
                    endwhile; 
                    ?>
                </div>
            </div>
        </div>
        
        <div class="payment-confirmation-container">
            <h2>Payment Confirmation</h2>
            <p class="confirmation-instruction">After completing your payment, please fill out the form below to confirm your payment.</p>
            
            <form action="" method="POST" enctype="multipart/form-data" class="payment-confirmation-form">
                <div class="form-group">
                    <label for="payment_method">Payment Method Used</label>
                    <select name="payment_method" id="payment_method" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" required>
                        <option value="">Select Payment Method</option>
                        <?php 
                        mysqli_data_seek($paymentResult, 0);
                        while($payment = mysqli_fetch_assoc($paymentResult)): 
                        ?>
                        <option value="<?php echo $payment['title']; ?>"><?php echo $payment['title']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transaction_id">Transaction ID / Reference Number</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" required>
                    <small class="form-text text-muted">Enter the transaction ID or reference number from your payment.</small>
                </div>
                
                <div class="form-group">
                    <label for="payment_date">Payment Date</label>
                    <input type="date" name="payment_date" id="payment_date" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_amount">Amount Paid</label>
                    <input type="number" name="payment_amount" id="payment_amount" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" value="<?php echo $order['total_amount']; ?>" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_screenshot">Payment Screenshot</label>
                    <div class="custom-file-upload <?php echo $isMobile ? 'mobile-file-upload' : ''; ?>">
                        <input type="file" name="payment_screenshot" id="payment_screenshot" accept="image/jpeg,image/png,image/jpg" required>
                        <label for="payment_screenshot">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span class="file-label">Choose File</span>
                        </label>
                        <div class="selected-file"></div>
                    </div>
                    <small class="form-text text-muted">Upload a screenshot of your payment confirmation (JPEG or PNG only).</small>
                </div>
                
                <div class="form-group">
                    <label for="payment_notes">Additional Notes (Optional)</label>
                    <textarea name="payment_notes" id="payment_notes" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .order-payment-container {
        margin: 30px 0;
    }
    
    .order-summary-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .card-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-header h2 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }
    
    .order-id {
        font-weight: bold;
        color: #555;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .order-details {
        margin-bottom: 20px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #555;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: bold;
    }
    
    .status-badge.pending {
        background-color: #ffeeba;
        color: #856404;
    }
    
    .status-badge.processing {
        background-color: #b8daff;
        color: #004085;
    }
    
    .status-badge.completed {
        background-color: #c3e6cb;
        color: #155724;
    }
    
    .status-badge.cancelled {
        background-color: #f5c6cb;
        color: #721c24;
    }
    
    .status-badge.pending_verification {
        background-color: #d6d8db;
        color: #383d41;
    }
    
    .order-amount {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
    }
    
    .amount-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .amount-row.total {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        font-weight: bold;
        font-size: 18px;
    }
    
    .amount-row.discount .amount-value {
        color: #28a745;
    }
    
    .payment-options-container,
    .payment-confirmation-container {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        padding: 20px;
    }
    
    .payment-instruction,
    .confirmation-instruction {
        margin-bottom: 20px;
        color: #555;
    }
    
    .payment-methods-tabs {
        margin-bottom: 30px;
    }
    
    .tabs-header {
        display: flex;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 20px;
    }
    
    .tab-btn {
        padding: 10px 20px;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        font-weight: 600;
        color: #555;
        transition: all 0.3s;
    }
    
    .tab-btn.active {
        color: #007bff;
        border-bottom-color: #007bff;
    }
    
    .tab-pane {
        display: none;
    }
    
    .tab-pane.active {
        display: block;
    }
    
    .payment-method-description {
        margin-bottom: 20px;
    }
    
    .payment-details {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .payment-details h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
    }
    
    .payment-details-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .payment-details-table th,
    .payment-details-table td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }
    
    .payment-details-table th {
        text-align: left;
        width: 40%;
        color: #555;
    }
    
    .copyable-field {
        display: flex;
        align-items: center;
    }
    
    .copy-btn {
        background: none;
        border: none;
        color: #007bff;
        cursor: pointer;
        margin-left: 10px;
        padding: 0;
    }
    
    .copy-btn:hover {
        color: #0056b3;
    }
    
    .payment-note {
        margin-top: 15px;
        padding: 10px;
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        color: #856404;
    }
    
    .upi-id-container {
        margin-bottom: 20px;
    }
    
    .qr-code-container {
        text-align: center;
        margin: 20px 0;
    }
    
    .qr-code-image {
        max-width: 200px;
        border: 1px solid #ddd;
        padding: 10px;
        background-color: #fff;
    }
    
    .download-qr-btn {
        display: inline-block;
        margin-top: 10px;
        color: #007bff;
        text-decoration: none;
    }
    
    .download-qr-btn:hover {
        text-decoration: underline;
    }
    
    .upi-apps {
        margin-top: 20px;
    }
    
    .upi-app-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .upi-app-btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 15px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #333;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .upi-app-btn:hover {
        background-color: #e9ecef;
    }
    
    .upi-app-btn i {
        margin-right: 8px;
    }
    
    .upi-app-btn.phonepe {
        background-color: #5f259f;
        color: #fff;
    }
    
    .upi-app-btn.gpay {
        background-color: #4285f4;
        color: #fff;
    }
    
    .upi-app-btn.paytm {
        background-color: #00baf2;
        color: #fff;
    }
    
    .payment-confirmation-form {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }
    
    .form-text {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: #6c757d;
    }
    
    .custom-file-upload {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .custom-file-upload input[type="file"] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    
    .custom-file-upload label {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .custom-file-upload label:hover {
        background-color: #e9ecef;
    }
    
    .custom-file-upload label i {
        margin-right: 10px;
    }
    
    .selected-file {
        margin-top: 10px;
        font-size: 14px;
        color: #28a745;
    }
    
    .form-actions {
        margin-top: 30px;
        text-align: center;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: #fff;
    }
    
    .btn-primary:hover {
        background-color: #0069d9;
    }
    
    /* Mobile Styles */
    .mobile-view .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mobile-view .order-id {
        margin-top: 5px;
    }
    
    .mobile-view .tabs-header {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 5px;
    }
    
    .mobile-view .tab-btn {
        padding: 8px 15px;
        font-size: 14px;
    }
    
    .mobile-view .payment-details-table th {
        width: 50%;
    }
    
    .mobile-view .upi-app-buttons {
        flex-direction: column;
    }
    
    .mobile-input {
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    .mobile-btn {
        width: 100%;
        padding: 12px 20px;
    }
    
    .mobile-file-upload label {
        padding: 12px 15px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show corresponding tab pane
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Copy to clipboard functionality
    const copyButtons = document.querySelectorAll('.copy-btn');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-clipboard-target');
            const textToCopy = document.querySelector(targetId).textContent;
            
            // Create temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            document.body.appendChild(textarea);
            
            // Select and copy text
            textarea.select();
            document.execCommand('copy');
            
            // Remove temporary textarea
            document.body.removeChild(textarea);
            
            // Show copied feedback
            const originalIcon = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i>';
            
            setTimeout(() => {
                this.innerHTML = originalIcon;
            }, 2000);
        });
    });
    
    // File upload preview
    const fileInput = document.getElementById('payment_screenshot');
    const fileLabel = document.querySelector('.file-label');
    const selectedFile = document.querySelector('.selected-file');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                selectedFile.textContent = fileName;
                fileLabel.textContent = 'File Selected';
            } else {
                selectedFile.textContent = '';
                fileLabel.textContent = 'Choose File';
            }
        });
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
