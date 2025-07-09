<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error'] = "Please login to view your orders";
    header("Location: customer_login.php?redirect=track_orders.php");
    exit();
}

// Get customer ID from session
$customer_id = $_SESSION['customer_id'];

// Function to get customer orders using PDO
function get_customer_orders($customer_id, $pdo) {
    $query = "SELECT o.*,
                     COUNT(oi.product_id) AS item_count
              FROM orders o
              LEFT JOIN order_items oi ON o.id = oi.order_id
              WHERE o.customer_id = :customer_id
              GROUP BY o.id
              ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get order items using PDO
function get_order_items($order_id, $pdo) {
    $query = "SELECT oi.product_id, oi.quantity, p.name, p.image, p.price
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = :order_id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all orders for the customer
try {
    $orders = get_customer_orders($customer_id, $pdo);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}

$page_title = "Track Your Orders | " . htmlspecialchars(SITE_NAME);
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Your Order History</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_SESSION['success']) ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">
                            You haven't placed any orders yet.
                        </div>
                        <a href="product.php" class="btn btn-primary">
                            Start Shopping
                        </a>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?= $order['id'] ?></td>
                                            <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                            <td><?= $order['item_count'] ?></td>
                                            <td>Ksh <?= number_format($order['total'], 2) ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch(strtolower($order['status'])) {
                                                    case 'pending':
                                                        $status_class = 'warning';
                                                        break;
                                                    case 'approved':
                                                    case 'completed':
                                                        $status_class = 'success';
                                                        break;
                                                    case 'shipped':
                                                        $status_class = 'info';
                                                        break;
                                                    case 'delivered':
                                                        $status_class = 'primary';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'danger';
                                                        break;
                                                    default:
                                                        $status_class = 'secondary';
                                                }
                                                ?>
                                                <span class="badge bg-<?= $status_class ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal" data-bs-target="#orderModal<?= $order['id'] ?>">
                                                    View Details
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Order Detail Modals -->
                        <?php foreach ($orders as $order): ?>
                            <?php
                            try {
                                $order_items = get_order_items($order['id'], $pdo);
                                $items_total = 0;
                                foreach ($order_items as $item) {
                                    $items_total += $item['price'] * $item['quantity'];
                                }
                            } catch (PDOException $e) {
                                $order_items = [];
                                $items_total = 0;
                                $_SESSION['error'] = "Error fetching order items: " . $e->getMessage();
                            }
                            ?>
                            <div class="modal fade" id="orderModal<?= $order['id'] ?>" tabindex="-1" aria-labelledby="orderModalLabel<?= $order['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="orderModalLabel<?= $order['id'] ?>">
                                                Order #<?= $order['id'] ?> Details
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <h6>Order Information</h6>
                                                    <p><strong>Order Date:</strong> <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></p>
                                                    <p><strong>Status:</strong> <span class="badge bg-<?= $status_class ?>"><?= ucfirst($order['status']) ?></span></p>
                                                    <?php if (strtolower($order['status']) === 'shipped' && !empty($order['tracking_number'])): ?>
                                                        <p><strong>Tracking #:</strong> <?= $order['tracking_number'] ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Payment Information</h6>
                                                    <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_method']) ?></p>
                                                    <p><strong>Order Total:</strong> Ksh <?= number_format($order['total'], 2) ?></p>
                                                </div>
                                            </div>

                                            <h6>Order Items (<?= count($order_items) ?> items)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Price</th>
                                                            <th>Quantity</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($order_items as $item): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($item['image'])): ?>
                                                                            <img src="/Sunstore-Project/admin/assets/products/<?= htmlspecialchars($item['image']) ?>"
                                                                                 class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                                        <?php endif; ?>
                                                                        <div><?= htmlspecialchars($item['name']) ?></div>
                                                                    </div>
                                                                </td>
                                                                <td>Ksh <?= number_format($item['price'], 2) ?></td>
                                                                <td><?= $item['quantity'] ?></td>
                                                                <td>Ksh <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="3" class="text-end">Items Subtotal:</th>
                                                            <th>Ksh <?= number_format($items_total, 2) ?></th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="3" class="text-end">Order Total:</th>
                                                            <th>Ksh <?= number_format($order['total'], 2) ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <h6>Customer Information</h6>
                                                    <p>
                                                        <strong>Name:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br>
                                                        <strong>Email:</strong> <?= htmlspecialchars($order['email']) ?><br>
                                                        <strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?><br>
                                                    </p>

                                                    <h6 class="mt-3">Shipping Address</h6>
                                                    <p>
                                                        <?= nl2br(htmlspecialchars($order['address'])) ?><br>
                                                        <?= htmlspecialchars($order['city']) ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php if (!empty($order['notes'])): ?>
                                                        <h6>Order Notes</h6>
                                                        <div class="alert alert-info">
                                                            <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <?php if (strtolower($order['status']) === 'pending'): ?>
                                                <button type="button" class="btn btn-danger">Cancel Order</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
