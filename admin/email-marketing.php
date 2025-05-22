<?php
session_start();

// Define admin path constant
define('ADMIN_PATH', true);

// Include database connection
include '../includes/db_connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save Email Settings
    if (isset($_POST['save_email_settings'])) {
        $email_service = mysqli_real_escape_string($conn, $_POST['email_service']);
        $api_key = mysqli_real_escape_string($conn, $_POST['api_key']);
        $from_email = mysqli_real_escape_string($conn, $_POST['from_email']);
        $from_name = mysqli_real_escape_string($conn, $_POST['from_name']);
        
        // Update settings in database
        $settings = [
            'email_service' => $email_service,
            'email_api_key' => $api_key,
            'email_from_email' => $from_email,
            'email_from_name' => $from_name
        ];
        
        foreach ($settings as $option_name => $option_value) {
            $check_query = "SELECT * FROM site_options WHERE option_name = '$option_name'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $update_query = "UPDATE site_options SET option_value = '$option_value' WHERE option_name = '$option_name'";
                mysqli_query($conn, $update_query);
            } else {
                $insert_query = "INSERT INTO site_options (option_name, option_value) VALUES ('$option_name', '$option_value')";
                mysqli_query($conn, $insert_query);
            }
        }
        
        $message = "Email settings saved successfully.";
    }
    
    // Create Email Campaign
    if (isset($_POST['create_campaign'])) {
        $name = mysqli_real_escape_string($conn, $_POST['campaign_name']);
        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        $recipient_type = mysqli_real_escape_string($conn, $_POST['recipient_type']);
        $status = 'draft';
        $admin_id = $_SESSION['admin_id'];
        
        // Insert campaign
        $query = "INSERT INTO email_campaigns (name, subject, content, recipient_type, status, created_by, created_at) 
                 VALUES ('$name', '$subject', '$content', '$recipient_type', '$status', $admin_id, NOW())";
        
        if (mysqli_query($conn, $query)) {
            $campaign_id = mysqli_insert_id($conn);
            $message = "Email campaign created successfully.";
        } else {
            $error = "Error creating campaign: " . mysqli_error($conn);
        }
    }
    
    // Send Test Email
    if (isset($_POST['send_test'])) {
        $campaign_id = intval($_POST['campaign_id']);
        $test_email = mysqli_real_escape_string($conn, $_POST['test_email']);
        
        // Get campaign details
        $campaign_query = "SELECT * FROM email_campaigns WHERE id = $campaign_id";
        $campaign_result = mysqli_query($conn, $campaign_query);
        
        if ($campaign_result && mysqli_num_rows($campaign_result) > 0) {
            $campaign = mysqli_fetch_assoc($campaign_result);
            
            // In a real implementation, you would send the email here
            // For this example, we'll just log it
            $log_query = "INSERT INTO email_logs (campaign_id, recipient, status, sent_at) 
                         VALUES ($campaign_id, '$test_email', 'sent', NOW())";
            mysqli_query($conn, $log_query);
            
            $message = "Test email sent to $test_email.";
        } else {
            $error = "Campaign not found.";
        }
    }
    
    // Send Campaign
    if (isset($_POST['send_campaign'])) {
        $campaign_id = intval($_POST['campaign_id']);
        
        // Update campaign status
        $update_query = "UPDATE email_campaigns SET status = 'sending', scheduled_at = NOW() WHERE id = $campaign_id";
        
        if (mysqli_query($conn, $update_query)) {
            // In a real implementation, you would queue the campaign for sending
            // For this example, we'll just update the status
            $message = "Campaign queued for sending.";
        } else {
            $error = "Error scheduling campaign: " . mysqli_error($conn);
        }
    }
}

// Get email settings
$email_settings_query = "SELECT * FROM site_options WHERE option_name LIKE 'email_%'";
$email_settings_result = mysqli_query($conn, $email_settings_query);
$email_settings = [];

while ($row = mysqli_fetch_assoc($email_settings_result)) {
    $key = str_replace('email_', '', $row['option_name']);
    $email_settings[$key] = $row['option_value'];
}

// Get email campaigns
$campaigns_query = "SELECT * FROM email_campaigns ORDER BY created_at DESC";
$campaigns_result = mysqli_query($conn, $campaigns_query);
$campaigns = mysqli_fetch_all($campaigns_result, MYSQLI_ASSOC);

// Get subscriber count
$subscribers_query = "SELECT COUNT(*) as total FROM newsletter_subscribers WHERE active = 1";
$subscribers_result = mysqli_query($conn, $subscribers_query);
$subscribers_count = mysqli_fetch_assoc($subscribers_result)['total'];

// Get customer count
$customers_query = "SELECT COUNT(*) as total FROM users";
$customers_result = mysqli_query($conn, $customers_query);
$customers_count = mysqli_fetch_assoc($customers_result)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Marketing - Fireworks Shop</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .campaign {
            background-color: #f5f5f5;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .campaign-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .campaign-body {
            margin-top: 15px;
            display: none;
        }
        
        .campaign.active .campaign-body {
            display: block;
        }
        
        .campaign-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .campaign-status.draft {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .campaign-status.sending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .campaign-status.sent {
            background-color: #d4edda;
            color: #155724;
        }
        
        .campaign-status.failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .email-stats {
            display: flex;
            margin-bottom: 20px;
        }
        
        .email-stat {
            flex: 1;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin-right: 10px;
            text-align: center;
        }
        
        .email-stat:last-child {
            margin-right: 0;
        }
        
        .email-stat h3 {
            margin-top: 0;
            color: #333;
        }
        
        .email-stat p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
        }
        
        .template-preview {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .template-preview:hover {
            border-color: #4caf50;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .template-preview.selected {
            border-color: #4caf50;
            background-color: #e8f5e9;
        }
        
        .template-preview h4 {
            margin-top: 0;
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
                    <h1>Email Marketing</h1>
                    <div class="actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
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
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Email Campaigns</h2>
                                    <button class="btn btn-sm btn-primary" id="create-campaign-btn">
                                        <i class="fas fa-plus"></i> Create Campaign
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="email-stats">
                                        <div class="email-stat">
                                            <h3>Subscribers</h3>
                                            <p><?php echo number_format($subscribers_count); ?></p>
                                        </div>
                                        
                                        <div class="email-stat">
                                            <h3>Customers</h3>
                                            <p><?php echo number_format($customers_count); ?></p>
                                        </div>
                                        
                                        <div class="email-stat">
                                            <h3>Campaigns</h3>
                                            <p><?php echo count($campaigns); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div id="campaigns-container">
                                        <?php if(count($campaigns) > 0): ?>
                                            <?php foreach($campaigns as $campaign): ?>
                                                <div class="campaign" data-id="<?php echo $campaign['id']; ?>">
                                                    <div class="campaign-header">
                                                        <div>
                                                            <strong><?php echo $campaign['name']; ?></strong>
                                                            <span class="campaign-status <?php echo $campaign['status']; ?>">
                                                                <?php echo ucfirst($campaign['status']); ?>
                                                            </span>
                                                        </div>
                                                        <i class="fas fa-chevron-down"></i>
                                                    </div>
                                                    <div class="campaign-body">
                                                        <div class="form-group">
                                                            <label>Subject</label>
                                                            <input type="text" class="form-control" value="<?php echo $campaign['subject']; ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label>Content</label>
                                                            <div class="form-control" style="height: auto; min-height: 100px; overflow-y: auto;">
                                                                <?php echo nl2br($campaign['content']); ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label>Recipients</label>
                                                            <input type="text" class="form-control" value="<?php echo ucfirst($campaign['recipient_type']); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label>Created</label>
                                                            <input type="text" class="form-control" value="<?php echo date('d M Y H:i', strtotime($campaign['created_at'])); ?>" readonly>
                                                        </div>
                                                        
                                                        <?php if($campaign['status'] == 'draft'): ?>
                                                            <form action="" method="POST" class="mb-3">
                                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                                
                                                                <div class="form-row">
                                                                    <div class="form-group col-md-8">
                                                                        <label for="test_email">Test Email</label>
                                                                        <input type="email" id="test_email" name="test_email" class="form-control" placeholder="Enter email address" required>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>&nbsp;</label>
                                                                        <button type="submit" name="send_test" class="btn btn-secondary btn-block">
                                                                            <i class="fas fa-paper-plane"></i> Send Test
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                            
                                                            <form action="" method="POST">
                                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                                
                                                                <button type="submit" name="send_campaign" class="btn btn-primary" onclick="return confirm('Are you sure you want to send this campaign?');">
                                                                    <i class="fas fa-paper-plane"></i> Send Campaign
                                                                </button>
                                                            </form>
                                                        <?php elseif($campaign['status'] == 'sent'): ?>
                                                            <div class="alert alert-success">
                                                                <p><strong>Campaign sent successfully!</strong></p>
                                                                <p>Sent on: <?php echo date('d M Y H:i', strtotime($campaign['sent_at'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                No email campaigns found. Click "Create Campaign" to create one.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Create Campaign Form (Hidden by default) -->
                                    <div id="create-campaign-form" style="display: none;">
                                        <h3>Create New Campaign</h3>
                                        <form action="" method="POST">
                                            <div class="form-group">
                                                <label for="campaign_name">Campaign Name</label>
                                                <input type="text" id="campaign_name" name="campaign_name" class="form-control" required>
                                                <small class="form-text text-muted">Internal name for your reference</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="subject">Email Subject</label>
                                                <input type="text" id="subject" name="subject" class="form-control" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="recipient_type">Recipients</label>
                                                <select id="recipient_type" name="recipient_type" class="form-control" required>
                                                    <option value="subscribers">Newsletter Subscribers</option>
                                                    <option value="customers">All Customers</option>
                                                    <option value="recent_customers">Recent Customers (Last 30 Days)</option>
                                                    <option value="abandoned_cart">Abandoned Cart Users</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Email Template</label>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="template-preview selected" data-template="newsletter">
                                                            <h4>Newsletter</h4>
                                                            <p>Standard newsletter template with header, content, and footer.</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="template-preview" data-template="promotion">
                                                            <h4>Promotion</h4>
                                                            <p>Template for special offers and promotions.</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="template-preview" data-template="abandoned_cart">
                                                            <h4>Abandoned Cart</h4>
                                                            <p>Template for abandoned cart recovery emails.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="content">Email Content</label>
                                                <textarea id="content" name="content" class="form-control" rows="10" required></textarea>
                                                <small class="form-text text-muted">You can use HTML for formatting. Use {{name}} to insert the recipient's name.</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <button type="submit" name="create_campaign" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Create Campaign
                                                </button>
                                                
                                                <button type="button" id="cancel-create-campaign" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Email Settings</h2>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="email_service">Email Service</label>
                                            <select id="email_service" name="email_service" class="form-control" required>
                                                <option value="smtp" <?php echo isset($email_settings['service']) && $email_settings['service'] == 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                                <option value="mailchimp" <?php echo isset($email_settings['service']) && $email_settings['service'] == 'mailchimp' ? 'selected' : ''; ?>>Mailchimp</option>
                                                <option value="sendgrid" <?php echo isset($email_settings['service']) && $email_settings['service'] == 'sendgrid' ? 'selected' : ''; ?>>SendGrid</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="api_key">API Key</label>
                                            <input type="text" id="api_key" name="api_key" class="form-control" value="<?php echo isset($email_settings['api_key']) ? $email_settings['api_key'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="from_email">From Email</label>
                                            <input type="email" id="from_email" name="from_email" class="form-control" value="<?php echo isset($email_settings['from_email']) ? $email_settings['from_email'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="from_name">From Name</label>
                                            <input type="text" id="from_name" name="from_name" class="form-control" value="<?php echo isset($email_settings['from_name']) ? $email_settings['from_name'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="save_email_settings" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Email Marketing Tips</h2>
                                </div>
                                <div class="card-body">
                                    <h4>Best Practices</h4>
                                    <ul>
                                        <li>Use clear and compelling subject lines</li>
                                        <li>Keep your emails concise and focused</li>
                                        <li>Include a clear call-to-action</li>
                                        <li>Personalize emails when possible</li>
                                        <li>Test emails before sending to your full list</li>
                                        <li>Analyze open and click rates to improve future campaigns</li>
                                    </ul>
                                    
                                    <h4>Campaign Ideas</h4>
                                    <ul>
                                        <li>New product announcements</li>
                                        <li>Special promotions and discounts</li>
                                        <li>Seasonal offers (Diwali, New Year, etc.)</li>
                                        <li>Educational content about fireworks safety</li>
                                        <li>Customer testimonials and reviews</li>
                                        <li>Abandoned cart recovery emails</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Toggle campaign details
        document.querySelectorAll('.campaign-header').forEach(header => {
            header.addEventListener('click', function() {
                const campaign = this.closest('.campaign');
                campaign.classList.toggle('active');
                
                const icon = this.querySelector('i');
                if (campaign.classList.contains('active')) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                } else {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
        });
        
        // Show/hide create campaign form
        document.getElementById('create-campaign-btn').addEventListener('click', function() {
            document.getElementById('create-campaign-form').style.display = 'block';
            this.style.display = 'none';
        });
        
        document.getElementById('cancel-create-campaign').addEventListener('click', function() {
            document.getElementById('create-campaign-form').style.display = 'none';
            document.getElementById('create-campaign-btn').style.display = 'inline-block';
        });
        
        // Template selection
        document.querySelectorAll('.template-preview').forEach(template => {
            template.addEventListener('click', function() {
                // Remove selected class from all templates
                document.querySelectorAll('.template-preview').forEach(t => {
                    t.classList.remove('selected');
                });
                
                // Add selected class to clicked template
                this.classList.add('selected');
                
                // Set template content based on selection
                const templateType = this.getAttribute('data-template');
                const contentField = document.getElementById('content');
                
                switch(templateType) {
                    case 'newsletter':
                        contentField.value = `<h1>Newsletter Title</h1>
<p>Hello {{name}},</p>
<p>Welcome to our latest newsletter. Here are our newest products:</p>
<ul>
    <li>Product 1</li>
    <li>Product 2</li>
    <li>Product 3</li>
</ul>
<p>Check out our <a href="{{store_url}}">store</a> for more products!</p>
<p>Thank you for subscribing to our newsletter.</p>
<p>Best regards,<br>The Fireworks Shop Team</p>`;
                        break;
                    case 'promotion':
                        contentField.value = `<h1>Special Offer!</h1>
<p>Hello {{name}},</p>
<p>We're excited to offer you a special discount on our products!</p>
<h2>Get 20% OFF your next purchase</h2>
<p>Use code: <strong>FIREWORKS20</strong> at checkout.</p>
<p>This offer is valid until [Date]. Don't miss out!</p>
<p><a href="{{store_url}}">Shop Now</a></p>
<p>Thank you for being our valued customer.</p>
<p>Best regards,<br>The Fireworks Shop Team</p>`;
                        break;
                    case 'abandoned_cart':
                        contentField.value = `<h1>Your Cart is Waiting!</h1>
<p>Hello {{name}},</p>
<p>We noticed you left some items in your shopping cart.</p>
<p>Here's what you left behind:</p>
<ul>
    <li>{{product_1}}</li>
    <li>{{product_2}}</li>
</ul>
<p>Would you like to complete your purchase? Your cart will be saved for the next 48 hours.</p>
<p><a href="{{cart_url}}">Return to Cart</a></p>
<p>If you have any questions, feel free to contact our customer support.</p>
<p>Best regards,<br>The Fireworks Shop Team</p>`;
                        break;
                }
            });
        });
    </script>
</body>
</html>
