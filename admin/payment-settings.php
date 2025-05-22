<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include '../includes/db_connect.php';

// Get admin information
$adminId = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admins WHERE id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

// Handle form submission for adding/editing payment details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new payment detail
        if ($_POST['action'] === 'add') {
            $paymentType = mysqli_real_escape_string($conn, $_POST['payment_type']);
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $accountName = mysqli_real_escape_string($conn, $_POST['account_name'] ?? '');
            $accountNumber = mysqli_real_escape_string($conn, $_POST['account_number'] ?? '');
            $ifscCode = mysqli_real_escape_string($conn, $_POST['ifsc_code'] ?? '');
            $bankName = mysqli_real_escape_string($conn, $_POST['bank_name'] ?? '');
            $branchName = mysqli_real_escape_string($conn, $_POST['branch_name'] ?? '');
            $upiId = mysqli_real_escape_string($conn, $_POST['upi_id'] ?? '');
            $displayOrder = (int)$_POST['display_order'];
            $active = isset($_POST['active']) ? 1 : 0;
            
            // Handle QR code image upload
            $qrCodeImage = '';
            if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === 0) {
                $uploadDir = '../uploads/payment/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['qr_code_image']['name']);
                $targetFile = $uploadDir . $fileName;
                
                // Check if image file is a actual image
                $check = getimagesize($_FILES['qr_code_image']['tmp_name']);
                if ($check !== false) {
                    // Upload file
                    if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $targetFile)) {
                        $qrCodeImage = $fileName;
                    }
                }
            }
            
            $insertQuery = "INSERT INTO payment_details (payment_type, title, description, account_name, account_number, ifsc_code, bank_name, branch_name, upi_id, qr_code_image, display_order, active) 
                           VALUES ('$paymentType', '$title', '$description', '$accountName', '$accountNumber', '$ifscCode', '$bankName', '$branchName', '$upiId', '$qrCodeImage', $displayOrder, $active)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $successMessage = "Payment detail added successfully";
            } else {
                $errorMessage = "Error adding payment detail: " . mysqli_error($conn);
            }
        }
        
        // Edit existing payment detail
        else if ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $paymentType = mysqli_real_escape_string($conn, $_POST['payment_type']);
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $accountName = mysqli_real_escape_string($conn, $_POST['account_name'] ?? '');
            $accountNumber = mysqli_real_escape_string($conn, $_POST['account_number'] ?? '');
            $ifscCode = mysqli_real_escape_string($conn, $_POST['ifsc_code'] ?? '');
            $bankName = mysqli_real_escape_string($conn, $_POST['bank_name'] ?? '');
            $branchName = mysqli_real_escape_string($conn, $_POST['branch_name'] ?? '');
            $upiId = mysqli_real_escape_string($conn, $_POST['upi_id'] ?? '');
            $displayOrder = (int)$_POST['display_order'];
            $active = isset($_POST['active']) ? 1 : 0;
            
            // Get current QR code image
            $currentImageQuery = "SELECT qr_code_image FROM payment_details WHERE id = $id";
            $currentImageResult = mysqli_query($conn, $currentImageQuery);
            $currentImageData = mysqli_fetch_assoc($currentImageResult);
            $qrCodeImage = $currentImageData['qr_code_image'];
            
            // Handle QR code image upload
            if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === 0) {
                $uploadDir = '../uploads/payment/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['qr_code_image']['name']);
                $targetFile = $uploadDir . $fileName;
                
                // Check if image file is a actual image
                $check = getimagesize($_FILES['qr_code_image']['tmp_name']);
                if ($check !== false) {
                    // Upload file
                    if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $targetFile)) {
                        // Delete old image if exists
                        if (!empty($qrCodeImage) && file_exists($uploadDir . $qrCodeImage)) {
                            unlink($uploadDir . $qrCodeImage);
                        }
                        $qrCodeImage = $fileName;
                    }
                }
            }
            
            $updateQuery = "UPDATE payment_details SET 
                           payment_type = '$paymentType',
                           title = '$title',
                           description = '$description',
                           account_name = '$accountName',
                           account_number = '$accountNumber',
                           ifsc_code = '$ifscCode',
                           bank_name = '$bankName',
                           branch_name = '$branchName',
                           upi_id = '$upiId',
                           qr_code_image = '$qrCodeImage',
                           display_order = $displayOrder,
                           active = $active,
                           updated_at = NOW()
                           WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $successMessage = "Payment detail updated successfully";
            } else {
                $errorMessage = "Error updating payment detail: " . mysqli_error($conn);
            }
        }
        
        // Delete payment detail
        else if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            // Get QR code image before deleting
            $imageQuery = "SELECT qr_code_image FROM payment_details WHERE id = $id";
            $imageResult = mysqli_query($conn, $imageQuery);
            $imageData = mysqli_fetch_assoc($imageResult);
            
            $deleteQuery = "DELETE FROM payment_details WHERE id = $id";
            
            if (mysqli_query($conn, $deleteQuery)) {
                // Delete QR code image if exists
                if (!empty($imageData['qr_code_image'])) {
                    $imagePath = '../uploads/payment/' . $imageData['qr_code_image'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                $successMessage = "Payment detail deleted successfully";
            } else {
                $errorMessage = "Error deleting payment detail: " . mysqli_error($conn);
            }
        }
    }
}

// Get all payment details
$paymentDetailsQuery = "SELECT * FROM payment_details ORDER BY display_order ASC";
$paymentDetailsResult = mysqli_query($conn, $paymentDetailsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '.tinymce-editor',
            height: 300,
            plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak',
            toolbar_mode: 'floating',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
        });
    </script>
    <style>
        .payment-details-container {
            margin-bottom: 30px;
        }
        .payment-detail-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .payment-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .payment-detail-title {
            font-size: 18px;
            font-weight: bold;
        }
        .payment-detail-actions {
            display: flex;
            gap: 10px;
        }
        .payment-detail-content {
            margin-bottom: 15px;
        }
        .payment-detail-info {
            margin-bottom: 5px;
        }
        .payment-detail-info strong {
            display: inline-block;
            width: 150px;
        }
        .payment-detail-qr {
            max-width: 200px;
            margin-top: 15px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-col {
            flex: 1;
        }
        .bank-fields, .upi-fields {
            display: none;
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
            
            <!-- Payment Settings Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Payment Settings</h1>
                    <button class="btn btn-primary" id="addPaymentBtn">Add New Payment Method</button>
                </div>
                
                <?php if(isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Payment Details List -->
                <div class="payment-details-container">
                    <?php if(mysqli_num_rows($paymentDetailsResult) > 0): ?>
                        <?php while($paymentDetail = mysqli_fetch_assoc($paymentDetailsResult)): ?>
                            <div class="payment-detail-card">
                                <div class="payment-detail-header">
                                    <div class="payment-detail-title">
                                        <?php echo $paymentDetail['title']; ?>
                                        <?php if(!$paymentDetail['active']): ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="payment-detail-actions">
                                        <button class="btn btn-sm btn-primary edit-payment-btn" data-id="<?php echo $paymentDetail['id']; ?>">Edit</button>
                                        <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this payment method?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $paymentDetail['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="payment-detail-content">
                                    <p><?php echo $paymentDetail['description']; ?></p>
                                    
                                    <?php if($paymentDetail['payment_type'] === 'bank'): ?>
                                        <div class="payment-detail-info"><strong>Account Name:</strong> <?php echo $paymentDetail['account_name']; ?></div>
                                        <div class="payment-detail-info"><strong>Account Number:</strong> <?php echo $paymentDetail['account_number']; ?></div>
                                        <div class="payment-detail-info"><strong>IFSC Code:</strong> <?php echo $paymentDetail['ifsc_code']; ?></div>
                                        <div class="payment-detail-info"><strong>Bank Name:</strong> <?php echo $paymentDetail['bank_name']; ?></div>
                                        <div class="payment-detail-info"><strong>Branch:</strong> <?php echo $paymentDetail['branch_name']; ?></div>
                                    <?php elseif($paymentDetail['payment_type'] === 'upi'): ?>
                                        <div class="payment-detail-info"><strong>UPI ID:</strong> <?php echo $paymentDetail['upi_id']; ?></div>
                                        <?php if(!empty($paymentDetail['qr_code_image'])): ?>
                                            <div class="payment-detail-info">
                                                <strong>QR Code:</strong>
                                                <div>
                                                    <img src="../uploads/payment/<?php echo $paymentDetail['qr_code_image']; ?>" alt="QR Code" class="payment-detail-qr">
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <div class="payment-detail-info"><strong>Display Order:</strong> <?php echo $paymentDetail['display_order']; ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">No payment methods found. Add your first payment method.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Add/Edit Payment Modal -->
                <div class="modal" id="paymentModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="modalTitle">Add New Payment Method</h2>
                            <span class="close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="paymentForm" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" id="formAction" value="add">
                                <input type="hidden" name="id" id="paymentId" value="">
                                
                                <div class="form-group">
                                    <label for="payment_type">Payment Type</label>
                                    <select id="payment_type" name="payment_type" class="form-control" required>
                                        <option value="">Select Payment Type</option>
                                        <option value="bank">Bank Transfer</option>
                                        <option value="upi">UPI Payment</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" id="title" name="title" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control tinymce-editor"></textarea>
                                </div>
                                
                                <!-- Bank Transfer Fields -->
                                <div id="bankFields" class="bank-fields">
                                    <div class="form-row">
                                        <div class="form-col">
                                            <div class="form-group">
                                                <label for="account_name">Account Name</label>
                                                <input type="text" id="account_name" name="account_name" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-col">
                                            <div class="form-group">
                                                <label for="account_number">Account Number</label>
                                                <input type="text" id="account_number" name="account_number" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-col">
                                            <div class="form-group">
                                                <label for="ifsc_code">IFSC Code</label>
                                                <input type="text" id="ifsc_code" name="ifsc_code" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-col">
                                            <div class="form-group">
                                                <label for="bank_name">Bank Name</label>
                                                <input type="text" id="bank_name" name="bank_name" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="branch_name">Branch Name</label>
                                        <input type="text" id="branch_name" name="branch_name" class="form-control">
                                    </div>
                                </div>
                                
                                <!-- UPI Fields -->
                                <div id="upiFields" class="upi-fields">
                                    <div class="form-group">
                                        <label for="upi_id">UPI ID</label>
                                        <input type="text" id="upi_id" name="upi_id" class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="qr_code_image">QR Code Image</label>
                                        <input type="file" id="qr_code_image" name="qr_code_image" class="form-control" accept="image/*">
                                        <div id="currentQrImage" style="margin-top: 10px; display: none;">
                                            <p>Current QR Code:</p>
                                            <img id="qrCodePreview" src="/placeholder.svg" alt="QR Code" style="max-width: 200px;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="display_order">Display Order</label>
                                            <input type="number" id="display_order" name="display_order" class="form-control" value="0" min="0">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group checkbox-group">
                                            <input type="checkbox" id="active" name="active" checked>
                                            <label for="active">Active</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('paymentModal');
            const addBtn = document.getElementById('addPaymentBtn');
            const closeBtn = document.querySelector('.close');
            const cancelBtn = document.getElementById('cancelBtn');
            const paymentTypeSelect = document.getElementById('payment_type');
            const bankFields = document.getElementById('bankFields');
            const upiFields = document.getElementById('upiFields');
            const editBtns = document.querySelectorAll('.edit-payment-btn');
            const form = document.getElementById('paymentForm');
            
            // Show modal when Add button is clicked
            addBtn.addEventListener('click', function() {
                document.getElementById('modalTitle').textContent = 'Add New Payment Method';
                document.getElementById('formAction').value = 'add';
                document.getElementById('paymentId').value = '';
                form.reset();
                modal.style.display = 'block';
                
                // Reset fields visibility
                bankFields.style.display = 'none';
                upiFields.style.display = 'none';
                
                // Reset TinyMCE
                tinymce.get('description').setContent('');
                
                // Hide QR code preview
                document.getElementById('currentQrImage').style.display = 'none';
            });
            
            // Close modal when X is clicked
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when Cancel is clicked
            cancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Show/hide fields based on payment type
            paymentTypeSelect.addEventListener('change', function() {
                if (this.value === 'bank') {
                    bankFields.style.display = 'block';
                    upiFields.style.display = 'none';
                } else if (this.value === 'upi') {
                    bankFields.style.display = 'none';
                    upiFields.style.display = 'block';
                } else {
                    bankFields.style.display = 'none';
                    upiFields.style.display = 'none';
                }
            });
            
            // Edit payment method
            editBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    
                    // Fetch payment detail data via AJAX
                    fetch('ajax/get_payment_detail.php?id=' + id)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('modalTitle').textContent = 'Edit Payment Method';
                            document.getElementById('formAction').value = 'edit';
                            document.getElementById('paymentId').value = data.id;
                            
                            document.getElementById('payment_type').value = data.payment_type;
                            document.getElementById('title').value = data.title;
                            tinymce.get('description').setContent(data.description || '');
                            document.getElementById('display_order').value = data.display_order;
                            document.getElementById('active').checked = data.active == 1;
                            
                            // Show/hide fields based on payment type
                            if (data.payment_type === 'bank') {
                                bankFields.style.display = 'block';
                                upiFields.style.display = 'none';
                                
                                document.getElementById('account_name').value = data.account_name || '';
                                document.getElementById('account_number').value = data.account_number || '';
                                document.getElementById('ifsc_code').value = data.ifsc_code || '';
                                document.getElementById('bank_name').value = data.bank_name || '';
                                document.getElementById('branch_name').value = data.branch_name || '';
                            } else if (data.payment_type === 'upi') {
                                bankFields.style.display = 'none';
                                upiFields.style.display = 'block';
                                
                                document.getElementById('upi_id').value = data.upi_id || '';
                                
                                // Show QR code preview if exists
                                if (data.qr_code_image) {
                                    document.getElementById('currentQrImage').style.display = 'block';
                                    document.getElementById('qrCodePreview').src = '../uploads/payment/' + data.qr_code_image;
                                } else {
                                    document.getElementById('currentQrImage').style.display = 'none';
                                }
                            }
                            
                            modal.style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Error fetching payment detail:', error);
                            alert('Failed to load payment detail data');
                        });
                });
            });
        });
    </script>
</body>
</html>
