<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Define email constants if not already defined in config.php
if (!defined('STORE_EMAIL')) {
    define('STORE_EMAIL', 'orders@sunstore.com');
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'SunStore Industries');
}
if (!defined('CURRENCY')) {
    define('CURRENCY', 'Ksh');
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Order ID not specified";
    header('Location: view.php');
    exit;
}

$order_id = intval($_GET['id']);

try {
    // Fetch order details
    $order_query = "
        SELECT 
            o.*,
            CONCAT(o.first_name, ' ', o.last_name) as customer_name,
            o.email as customer_email,
            o.phone as customer_phone,
            CONCAT(o.address, ', ', o.city) as customer_address,
            CONCAT(o.address, ', ', o.city) as shipping_address,
            CONCAT(o.address, ', ', o.city) as billing_address,
            o.total as total_amount,
            o.id as order_number
        FROM orders o
        WHERE o.id = ?
    ";
    
    $stmt = $pdo->prepare($order_query);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['error'] = "Order not found";
        header('Location: view.php');
        exit;
    }

    // Fetch order items
    $items_query = "
        SELECT 
            oi.*, 
            p.name as product_name, 
            p.image as product_image,
            p.sku
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ";
    $stmt = $pdo->prepare($items_query);
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $new_status = $_POST['status'] ?? 'pending';
        $new_payment_status = $_POST['payment_status'] ?? 'pending';
        $notes = trim($_POST['notes'] ?? '');
        
        // Get previous status for comparison
        $previous_status = $order['status'];
        $previous_payment_status = $order['payment_method'];
        
        // Update query without updated_at field
        $update_query = "
            UPDATE orders 
            SET 
                status = ?, 
                payment_method = ?, 
                notes = ?
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($update_query);
        $stmt->execute([$new_status, $new_payment_status, $notes, $order_id]);
        
        // Check if status changed to completed and paid
        if ($new_status === 'completed' && $new_payment_status === 'paid' && 
            ($previous_status !== 'completed' || $previous_payment_status !== 'paid')) {
            
            // Send confirmation email
            $to = $order['customer_email'];
            $subject = "Your Order #" . $order['order_number'] . " is Confirmed";
            
            // HTML email content
            $message = '
            <html>
            <head>
                <title>Order Confirmation</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 1px solid #eee; }
                    .content { padding: 20px; }
                    .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; }
                    .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .order-table th { background-color: #f2f2f2; text-align: left; padding: 10px; }
                    .order-table td { padding: 10px; border-bottom: 1px solid #eee; }
                    .button { background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2 style="color: #28a745;">Your Order is Confirmed!</h2>
                    </div>
                    <div class="content">
                        <p>Dear ' . htmlspecialchars($order['customer_name']) . ',</p>
                        
                        <p>Thank you for shopping with ' . SITE_NAME . '! We\'re pleased to inform you that your order has been confirmed and we\'re preparing it for shipment.</p>
                        
                        <h3 style="margin-top: 25px;">Order Summary</h3>
                        <table class="order-table">
                            <tr>
                                <th>Order Number:</th>
                                <td>#' . $order['order_number'] . '</td>
                            </tr>
                            <tr>
                                <th>Order Date:</th>
                                <td>' . date('F j, Y', strtotime($order['created_at'])) . '</td>
                            </tr>
                            <tr>
                                <th>Total Amount:</th>
                                <td><strong>' . CURRENCY . ' ' . number_format($order['total_amount'], 2) . '</strong></td>
                            </tr>
                        </table>
                        
                        <h3 style="margin-top: 25px;">Delivery Information</h3>
                        <p>Your order will be shipped to:</p>
                        <p>' . nl2br(htmlspecialchars($order['shipping_address'])) . '</p>
                        
                        <p style="margin-top: 20px;">We will notify you when your order has been dispatched. The delivery process typically takes 3-5 business days.</p>
                        
                        <p>You can view your order details anytime by visiting our website or contacting our customer support.</p>
                        
                        <p style="margin-top: 30px;">
                            <a href="' . (defined('SITE_URL') ? SITE_URL : '#') . '" class="button">Visit Our Store</a>
                        </p>
                        
                        <p>Thank you for choosing ' . SITE_NAME . '!</p>
                    </div>
                    <div class="footer">
                        <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                        <p>If you have any questions, please reply to this email or contact us at ' . STORE_EMAIL . '</p>
                    </div>
                </div>
            </body>
            </html>
            ';
            
            // Email headers
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: ' . SITE_NAME . ' <' . STORE_EMAIL . '>' . "\r\n";
            $headers .= 'Reply-To: ' . STORE_EMAIL . "\r\n";
            $headers .= 'X-Mailer: PHP/' . phpversion();
            
            // Send email
            if (mail($to, $subject, $message, $headers)) {
                $_SESSION['message'] = 'Order status updated and confirmation email sent successfully';
            } else {
                $_SESSION['message'] = 'Order status updated but email could not be sent';
                error_log("Failed to send order confirmation email to: " . $to);
            }
        } else {
            $_SESSION['message'] = 'Order status updated successfully';
        }
        
        header("Location: details.php?id=$order_id");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error updating order status: ' . $e->getMessage();
        error_log("Order status update error: " . $e->getMessage());
    }
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Order #<?= htmlspecialchars($order['order_number'] ?? 'N/A') ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-8">
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Order Items</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 45%">Product</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($item['product_image'])): ?>
                                            <div class="flex-shrink-0 me-3">
                                                <img src="../../<?= htmlspecialchars($item['product_image']) ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>" 
                                                     class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></h6>
                                            <?php if (!empty($item['sku'])): ?>
                                                <small class="text-muted">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end align-middle">
                                    <span class="text-nowrap"><?= CURRENCY . number_format($item['price'] ?? 0, 2) ?></span>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-dark rounded-pill px-3 py-1">
                                        <?= $item['quantity'] ?? 0 ?>
                                    </span>
                                </td>
                                <td class="text-end align-middle">
                                    <span class="text-nowrap fw-semibold"><?= CURRENCY . number_format($item['total'] ?? 0, 2) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                No items found for this order
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-group-divider">
                    <tr>
                        <td colspan="3" class="text-end fw-semibold">Subtotal:</td>
                        <td class="text-end fw-semibold"><?= CURRENCY . number_format($order['total_amount'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-semibold">Shipping:</td>
                        <td class="text-end fw-semibold"><?= CURRENCY . '0.00' ?></td>
                    </tr>
                    <tr class="border-top">
                        <td colspan="3" class="text-end fw-bold">Total:</td>
                        <td class="text-end fw-bold text-primary"><?= CURRENCY . number_format($order['total_amount'] ?? 0, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
                    
                    <div class="card">
                        <div class="card-header">
                            Order Notes
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary">Save Notes</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            Order Details
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="pending" <?= ($order['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= ($order['status'] ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="completed" <?= ($order['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= ($order['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Status</label>
                                    <select class="form-select" name="payment_status">
                                        <option value="pending" <?= ($order['payment_method'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="paid" <?= ($order['payment_method'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="failed" <?= ($order['payment_method'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                                        <option value="refunded" <?= ($order['payment_method'] ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Order Date</label>
                                    <input type="text" class="form-control" value="<?= date('M j, Y H:i', strtotime($order['created_at'] ?? 'now')) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>" readonly>
                                </div>
                                
                                <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            Customer Details
                        </div>
                        <div class="card-body">
                            <h6><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></h6>
                            <p class="mb-1">
                                <i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-phone me-2"></i> <?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i> <?= htmlspecialchars($order['customer_address'] ?? 'Address not available') ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            Shipping & Billing
                        </div>
                        <div class="card-body">
                            <h6>Shipping Address</h6>
                            <p class="mb-3"><?= nl2br(htmlspecialchars($order['shipping_address'] ?? 'Same as billing address')) ?></p>
                            
                            <h6>Billing Address</h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($order['billing_address'] ?? 'Address not specified')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>