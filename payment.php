<?php
// Include database connection
include 'includes/db_connect.php';

// Set page title
$pageTitle = "Payment Information";

// Include header
include 'includes/header.php';

// Get payment details from database
$paymentQuery = "SELECT * FROM payment_details WHERE active = 1 ORDER BY display_order ASC";
$paymentResult = mysqli_query($conn, $paymentQuery);
?>

<div class="container">
    <div class="page-header">
        <h1>Payment Information</h1>
    </div>
    
    <div class="payment-info-container">
        <div class="payment-intro">
            <p>We offer multiple payment options for your convenience. Please find the details below:</p>
        </div>
        
        <?php if(mysqli_num_rows($paymentResult) > 0): ?>
            <?php while($payment = mysqli_fetch_assoc($paymentResult)): ?>
                <div class="payment-method-card">
                    <div class="payment-method-header">
                        <h2><?php echo $payment['title']; ?></h2>
                    </div>
                    
                    <div class="payment-method-content">
                        <div class="payment-method-description">
                            <?php echo $payment['description']; ?>
                        </div>
                        
                        <?php if($payment['payment_type'] === 'bank'): ?>
                            <div class="payment-method-details">
                                <h3>Bank Account Details</h3>
                                <table class="payment-details-table">
                                    <tr>
                                        <th>Account Name</th>
                                        <td><?php echo $payment['account_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Account Number</th>
                                        <td><?php echo $payment['account_number']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>IFSC Code</th>
                                        <td><?php echo $payment['ifsc_code']; ?></td>
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
                            </div>
                        <?php elseif($payment['payment_type'] === 'upi'): ?>
                            <div class="payment-method-details">
                                <h3>UPI Details</h3>
                                <p class="upi-id"><strong>UPI ID:</strong> <?php echo $payment['upi_id']; ?></p>
                                
                                <?php if(!empty($payment['qr_code_image'])): ?>
                                    <div class="qr-code-container">
                                        <p>Scan QR Code to Pay:</p>
                                        <img src="uploads/payment/<?php echo $payment['qr_code_image']; ?>" alt="QR Code" class="qr-code-image">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-payment-methods">
                <p>No payment methods are currently available. Please contact us for payment information.</p>
            </div>
        <?php endif; ?>
        
        <div class="payment-notes">
            <h3>Important Notes</h3>
            <ul>
                <li>Please include your order number in the payment reference/description.</li>
                <li>After making the payment, please send the payment screenshot to our WhatsApp number for faster order processing.</li>
                <li>For any payment-related queries, please contact our customer support.</li>
            </ul>
        </div>
    </div>
</div>

<style>
    .payment-info-container {
        margin: 30px 0;
    }
    
    .payment-intro {
        margin-bottom: 30px;
    }
    
    .payment-method-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .payment-method-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .payment-method-header h2 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }
    
    .payment-method-content {
        padding: 20px;
    }
    
    .payment-method-description {
        margin-bottom: 20px;
    }
    
    .payment-method-details {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
    }
    
    .payment-method-details h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
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
    
    .upi-id {
        font-size: 18px;
        margin-bottom: 15px;
    }
    
    .qr-code-container {
        text-align: center;
        margin-top: 20px;
    }
    
    .qr-code-image {
        max-width: 200px;
        border: 1px solid #ddd;
        padding: 10px;
        background-color: #fff;
    }
    
    .payment-notes {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 30px;
    }
    
    .payment-notes h3 {
        margin-top: 0;
        color: #333;
    }
    
    .payment-notes ul {
        margin-bottom: 0;
    }
    
    .payment-notes li {
        margin-bottom: 10px;
    }
    
    .payment-notes li:last-child {
        margin-bottom: 0;
    }
    
    .no-payment-methods {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .payment-details-table th {
            width: 50%;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
