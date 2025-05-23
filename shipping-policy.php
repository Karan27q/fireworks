<?php
// Include database connection
include 'includes/db_connect.php';

// Get shipping policy content
$policyQuery = "SELECT * FROM pages WHERE slug = 'shipping-policy' AND active = 1";
$policyResult = mysqli_query($conn, $policyQuery);

if (!$policyResult) {
    die("Query failed: " . mysqli_error($conn));
}

if (mysqli_num_rows($policyResult) === 0) {
    header('Location: index.php');
    exit;
}

$policyPage = mysqli_fetch_assoc($policyResult);

// Set page title
$pageTitle = $policyPage['title'];

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="shipping-policy-page">
    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($policyPage['title']); ?></h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <span><?php echo htmlspecialchars($policyPage['title']); ?></span>
            </div>
        </div>

        <div class="policy-content">
            <?php echo $policyPage['content']; ?>
        </div>
    </div>
</main>

<style>
.shipping-policy-page {
    padding: 40px 0;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 10px;
}

.breadcrumb {
    color: #666;
}

.breadcrumb a {
    color: #007bff;
    text-decoration: none;
}

.policy-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.policy-content h1 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 1.5rem;
}

.policy-content h2 {
    font-size: 1.5rem;
    color: #444;
    margin: 2rem 0 1rem;
}

.policy-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.policy-content ul {
    list-style-type: disc;
    margin-left: 20px;
    margin-bottom: 1rem;
}

.policy-content li {
    color: #666;
    line-height: 1.6;
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .shipping-policy-page {
        padding: 20px 0;
    }

    .page-header h1 {
        font-size: 2rem;
    }

    .policy-content {
        padding: 20px;
    }

    .policy-content h1 {
        font-size: 1.75rem;
    }

    .policy-content h2 {
        font-size: 1.25rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 