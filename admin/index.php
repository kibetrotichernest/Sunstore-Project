<?php
// Start session and check authentication at the very top
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Force login check
require_login();

// Create database connection if it doesn't exist
if (!isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
}

// Initialize counts to prevent undefined variable errors
$products_count = $orders_count = $inquiries_count = $subscribers_count = $projects_count = 0;

// Get counts for dashboard with error handling
try {
    $products_count = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0] ?? 0;
    $orders_count = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0] ?? 0;
    $inquiries_count = $conn->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'new'")->fetch_row()[0] ?? 0;
    $subscribers_count = $conn->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = TRUE")->fetch_row()[0] ?? 0;
    $projects_count = $conn->query("SELECT COUNT(*) FROM projects")->fetch_row()[0] ?? 0;

    // Recent orders query with proper error handling
    $recent_orders_result = $conn->query("SELECT 
        o.order_id,
        o.tracking_number,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
        o.total_amount,
        o.status,
        o.created_at
    FROM orders o
    JOIN customers c ON o.customer_id = c.customer_id
    ORDER BY o.created_at DESC 
    LIMIT 5");

    if (!$recent_orders_result) {
        throw new Exception("Error fetching recent orders: " . $conn->error);
    }

} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = "Could not load dashboard data. Please try again later.";
}
?>

<?php include 'includes/admin-header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <!-- Products Card -->
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Products</h5>
                                    <h2 class="mb-0"><?= htmlspecialchars($products_count) ?></h2>
                                </div>
                                <i class="fas fa-box-open fa-3x"></i>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="products/view.php" class="text-white">View All</a>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Card -->
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Orders</h5>
                                    <h2 class="mb-0"><?= htmlspecialchars($orders_count) ?></h2>
                                </div>
                                <i class="fas fa-shopping-cart fa-3x"></i>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="orders/view.php" class="text-white">View All</a>
                        </div>
                    </div>
                </div>
                
                <!-- Inquiries Card -->
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">New Inquiries</h5>
                                    <h2 class="mb-0"><?= htmlspecialchars($inquiries_count) ?></h2>
                                </div>
                                <i class="fas fa-question-circle fa-3x"></i>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="inquiries/contact.php" class="text-white">View All</a>
                        </div>
                    </div>
                </div>
                
                <!-- Subscribers Card -->
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Subscribers</h5>
                                    <h2 class="mb-0"><?= htmlspecialchars($subscribers_count) ?></h2>
                                </div>
                                <i class="fas fa-users fa-3x"></i>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="subscribers/view.php" class="text-white">View All</a>
                        </div>
                    </div>
                </div>
                
                <!-- Projects Card -->
                <div class="col-md-3 mt-3">
                    <div class="card text-white bg-secondary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Projects</h5>
                                    <h2 class="mb-0"><?= htmlspecialchars($projects_count) ?></h2>
                                </div>
                                <i class="fas fa-solar-panel fa-3x"></i>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="projects/view.php" class="text-white">Manage Projects</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($recent_orders_result)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Tracking #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = $recent_orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                    <td><?= htmlspecialchars($order['tracking_number']) ?></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= CURRENCY . number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            match($order['status']) {
                                                'confirmed' => 'primary',
                                                'processing' => 'warning',
                                                'shipped' => 'info',
                                                'delivered' => 'success',
                                                default => 'secondary'
                                            }
                                        ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="orders/details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No recent orders data available</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>