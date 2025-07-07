<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: products.php");
    exit();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get order details
$order = get_order_details($order_id);
if (!$order) {
    header("Location: products.php");
    exit();
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#28a745" class="bi bi-check-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                        </svg>
                    </div>
                    <h2 class="mb-3">Order Confirmed!</h2>
                    <p class="lead mb-4">Thank you for your order. Your order number is <strong>#<?= $order_id ?></strong></p>
                    
                    <?php if ($order['payment_method'] === 'mpesa'): ?>
                        <div class="alert alert-success mb-4">
                            <h5><i class="fas fa-mobile-alt"></i> M-Pesa Payment Received</h5>
                            <p>We've received your payment of <strong>Ksh <?= number_format($order['total_amount'], 2) ?></strong> via M-Pesa.</p>
                            <p>Payment confirmation sent to <?= htmlspecialchars($order['mpesa_phone']) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            <h5><i class="fas fa-money-bill-wave"></i> Pay on Delivery</h5>
                            <p>Please have <strong>Ksh <?= number_format($order['total_amount'], 2) ?></strong> ready for payment upon delivery.</p>
                        </div>
                    <?php endif; ?>
                    
                    <p>We've sent a confirmation email with your order details.</p>
                    
                    <div class="d-flex justify-content-center gap-3 mt-5">
                        <a href="products.php" class="btn btn-outline-primary px-4">Continue Shopping</a>
                        <a href="order_details.php?id=<?= $order_id ?>" class="btn btn-primary px-4">View Order Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>