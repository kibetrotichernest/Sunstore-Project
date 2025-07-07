<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(o.order_number LIKE :search OR c.first_name LIKE :search OR c.last_name LIKE :search OR c.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status)) {
    $where[] = "o.status = :status";
    $params[':status'] = $status;
}

if (!empty($payment_status)) {
    $where[] = "o.payment_status = :payment_status";
    $params[':payment_status'] = $payment_status;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM `orders` o 
                LEFT JOIN `customers` c ON o.`customer_id` = c.`customer_id` 
                $where_clause";
$count_stmt = $pdo->prepare($count_query);

foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}

$count_stmt->execute();
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Main query
$query = "
    SELECT 
        o.`id` as order_id,
        o.`id` as order_number,
        o.`customer_id`,
        o.`first_name`,
        o.`last_name`,
        o.`email` as customer_email,
        o.`phone` as customer_phone,
        o.`total` as total_amount,
        o.`status`,
        o.`payment_method` as payment_status,
        o.`tracking_number`,
        o.`created_at`,
        CONCAT(o.`first_name`, ' ', o.`last_name`) as customer_name
    FROM `orders` o
    $where_clause
    ORDER BY o.`created_at` DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);

// Bind all parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Bind pagination parameters
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Orders</h1>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filters
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="payment_status">
                                <option value="">All Payment Statuses</option>
                                <option value="pending" <?= $payment_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= $payment_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="failed" <?= $payment_status === 'failed' ? 'selected' : '' ?>>Failed</option>
                                <option value="refunded" <?= $payment_status === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="view.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($order['customer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td><?= CURRENCY . number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $order['status'] === 'completed' ? 'success' : 
                                            ($order['status'] === 'processing' ? 'info' : 
                                            ($order['status'] === 'cancelled' ? 'danger' : 'warning')) 
                                        ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $order['payment_status'] === 'paid' ? 'success' : 
                                            ($order['payment_status'] === 'failed' ? 'danger' : 
                                            ($order['payment_status'] === 'refunded' ? 'secondary' : 'warning')) 
                                        ?>">
                                            <?= ucfirst($order['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                      <a href="details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary" title="View">                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>