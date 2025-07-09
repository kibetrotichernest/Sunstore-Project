<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error'] = "Please login to access your account";
    header("Location: ../customer_login.php");
    exit();
}

// Initialize variables
$customer = [];
$recent_orders = [];
$error = '';

try {
    // Get customer ID from session
    $customer_id = (int)$_SESSION['customer_id'];

    // Fetch customer details
    $stmt = $pdo->prepare("
        SELECT
            customer_id,
            first_name,
            last_name,
            email,
            phone,
            address,
            city,
            county,
            created_at
        FROM customers
        WHERE customer_id = :customer_id
        LIMIT 1
    ");
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $error = "Your account information couldn't be found. Please contact support.";
    } else {
        // Fetch recent orders (last 5) with proper table references
        $orders_stmt = $pdo->prepare("
            SELECT
                o.id AS order_id,
                o.created_at,
                o.total,
                o.status,
                COUNT(oi.item_id) AS item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.customer_id = :customer_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $orders_stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $orders_stmt->execute();
        $recent_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error in customer_account.php: " . $e->getMessage());
    $error = "We're experiencing technical difficulties. Please try again later.";
}

$page_title = "My Account | " . htmlspecialchars(SITE_NAME);
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">My Account</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="customer_account.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user-circle me-2"></i> Account Overview
                    </a>
                    <a href="../track_orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-history me-2"></i> Order History
                    </a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-lock me-2"></i> Change Password
                    </a>
                    <a href="../customer_logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($customer)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Account Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">Personal Information</h6>
                                    <div class="d-flex mb-3">
                                        <div style="width: 120px;"><strong>Name:</strong></div>
                                        <div><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></div>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <div style="width: 120px;"><strong>Email:</strong></div>
                                        <div><?= htmlspecialchars($customer['email']) ?></div>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <div style="width: 120px;"><strong>Phone:</strong></div>
                                        <div><?= !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : 'Not provided' ?></div>
                                    </div>
                                    <div class="d-flex">
                                        <div style="width: 120px;"><strong>Member Since:</strong></div>
                                        <div><?= date('F j, Y', strtotime($customer['created_at'])) ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">Address</h6>
                                    <div class="mb-3">
                                        <div class="p-3 bg-light rounded">
                                            <?php if (!empty($customer['address'])): ?>
                                                <?= nl2br(htmlspecialchars($customer['address'])) ?><br>
                                                <?= !empty($customer['city']) ? htmlspecialchars($customer['city']) . '<br>' : '' ?>
                                                <?= !empty($customer['county']) ? htmlspecialchars($customer['county']) : '' ?>
                                            <?php else: ?>
                                                No address saved
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="edit_profile.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit Address
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?= $order['order_id'] ?></td>
                                                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                                <td><?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></td>
                                                <td><?= CURRENCY . number_format($order['total'], 2) ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'completed' => 'success',
                                                        'shipped' => 'primary',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger',
                                                        'refunded' => 'secondary'
                                                    ][strtolower($order['status'])] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $status_class ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../track_orders.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="../track_orders.php" class="btn btn-primary">
                                    View All Orders <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h5>No orders yet</h5>
                                <p class="text-muted">You haven't placed any orders with us yet.</p>
                                <a href="product.php?view=all" class="btn btn-primary">
                                    Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
