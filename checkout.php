<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize messages and preserve form data
$_SESSION['order_message'] = '';
$_SESSION['order_success'] = false;
if (!isset($_SESSION['form_data'])) {
    $_SESSION['form_data'] = [];
}

// Handle M-Pesa payment initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_mpesa'])) {
    try {
        // Save all form data to session
        $_SESSION['form_data'] = $_POST;
        
        // Validate M-Pesa phone number
        $phone = preg_replace('/[^0-9]/', '', $_POST['mpesa_phone']);
        if (empty($phone) || !preg_match('/^[0-9]{10,12}$/', $phone)) {
            throw new Exception('Please enter a valid M-Pesa phone number (format: 07XXXXXXXX)');
        }
        
        // Store in session
        $_SESSION['mpesa_phone'] = $phone;
        
        // Calculate amount (including delivery fee)
        $delivery_fee = ($_POST['county'] === 'Nairobi') ? 0 : 500;
        $order_totals = calculate_cart_totals($delivery_fee);
        $amount = $order_totals['total_amount'];
        
        // Call M-Pesa API (simulated)
        $transaction_id = 'MPESA' . time() . rand(100, 999);
        
        $_SESSION['mpesa_transaction_id'] = $transaction_id;
        $_SESSION['mpesa_payment_initiated'] = true;
        $_SESSION['mpesa_amount'] = $amount;
        
        // Success message
        $_SESSION['order_message'] = 'Payment request sent to ' . $phone . '. Please complete payment on your phone.';
        $_SESSION['order_success'] = true;
        
        header('Location: checkout.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['order_success'] = false;
        $_SESSION['order_message'] = 'M-Pesa initiation failed: ' . $e->getMessage();
        header('Location: checkout.php');
        exit;
    }
}

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        // Validate required fields
        $required = ['first_name', 'last_name', 'email', 'phone', 'address', 'payment_method', 'county'];
        $missing = array_diff($required, array_keys(array_filter($_POST)));
        
        if (!empty($missing)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missing));
        }

        // Calculate order totals with delivery fee
        $delivery_fee = ($_POST['county'] === 'Nairobi') ? 0 : 500;
        $order_totals = calculate_cart_totals($delivery_fee);
        
        // Process payment based on method
        $payment_status = 'pending';
        $payment_details = [];
        
        if ($_POST['payment_method'] === 'cash_on_delivery') {
            $payment_status = 'pending';
            $payment_details = [
                'method' => 'cash_on_delivery',
                'status' => 'pending'
            ];
            
            // Clear any M-Pesa session data
            unset($_SESSION['mpesa_payment_initiated'], $_SESSION['mpesa_phone'], $_SESSION['mpesa_transaction_id']);
        } 
        elseif ($_POST['payment_method'] === 'mpesa') {
            if (!isset($_SESSION['mpesa_payment_initiated']) || !$_SESSION['mpesa_payment_initiated']) {
                throw new Exception("M-Pesa payment not initiated");
            }
            
            $payment_status = 'completed';
            $payment_details = [
                'method' => 'mpesa',
                'phone' => $_SESSION['mpesa_phone'],
                'transaction_id' => $_SESSION['mpesa_transaction_id'],
                'amount' => $_SESSION['mpesa_amount'],
                'status' => 'completed'
            ];
        }

        // Create order record
        $order_id = create_order([
            'customer' => $_POST,
            'totals' => $order_totals,
            'items' => $_SESSION['cart'],
            'payment_status' => $payment_status,
            'payment_details' => json_encode($payment_details)
        ]);

        if (!$order_id) {
            throw new Exception('Failed to create order record');
        }

        // Clear cart and payment session on success
        unset(
            $_SESSION['cart'], 
            $_SESSION['mpesa_payment_initiated'], 
            $_SESSION['mpesa_phone'], 
            $_SESSION['mpesa_transaction_id'],
            $_SESSION['mpesa_amount'],
            $_SESSION['form_data']
        );
        
        $_SESSION['order_success'] = true;
        $_SESSION['order_message'] = 'Order #' . $order_id . ' placed successfully!';
        $_SESSION['order_id'] = $order_id;

        header('Location: order_success.php?id=' . $order_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['order_success'] = false;
        $_SESSION['order_message'] = 'Order failed: ' . $e->getMessage();
        error_log('Order Error: ' . $e->getMessage());
        header('Location: checkout.php');
        exit;
    }
}

// Display checkout page
include 'includes/header.php';
?>
<!-- Checkout Form -->
<div class="container py-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Shipping Information</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($_SESSION['order_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['order_success'] ? 'success' : 'danger' ?>">
                            <?= $_SESSION['order_message'] ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="checkout.php" id="checkoutForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name*</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['first_name'] ?? $_SESSION['customer']['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name*</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['last_name'] ?? $_SESSION['customer']['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email*</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? $_SESSION['customer']['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number*</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['phone'] ?? $_SESSION['customer']['phone'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address*</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['address'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City*</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['city'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="county" class="form-label">County*</label>
                                <select class="form-select" id="county" name="county" required>
                                    <option value="">Select County</option>
                                    <option value="Nairobi" <?= ($_SESSION['form_data']['county'] ?? '') === 'Nairobi' ? 'selected' : '' ?>>Nairobi</option>
                                    <option value="Mombasa" <?= ($_SESSION['form_data']['county'] ?? '') === 'Mombasa' ? 'selected' : '' ?>>Mombasa</option>
                                    <!-- Add other counties -->
                                </select>
                            </div>
                        </div>
                        
                        <!-- Payment Methods -->
                        <div class="payment-methods mt-4">
                            <h5 class="mb-3">Payment Method</h5>
                            
                            <!-- M-Pesa Option -->
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="mpesa" value="mpesa" 
                                    <?= (!isset($_SESSION['form_data']['payment_method']) || (isset($_SESSION['form_data']['payment_method']) && $_SESSION['form_data']['payment_method'] === 'mpesa')) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mpesa">
                                    M-Pesa
                                </label>
                                <div id="mpesa-details" class="mt-2 p-3 bg-light rounded">
                                    <div class="form-group">
                                        <label for="mpesa_phone">M-Pesa Phone Number</label>
                                        <input type="tel" class="form-control" id="mpesa_phone" name="mpesa_phone" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['mpesa_phone'] ?? '') ?>" 
                                               placeholder="e.g. 0712345678" pattern="[0-9]{10,12}" required>
                                        <small class="form-text text-muted">You'll receive a payment request on this number</small>
                                    </div>
                                    <button type="submit" name="initiate_mpesa" class="btn btn-primary mt-2">
                                        Initiate M-Pesa Payment
                                    </button>
                                    
                                    <?php if (isset($_SESSION['mpesa_payment_initiated']) && $_SESSION['mpesa_payment_initiated']): ?>
                                        <div class="alert alert-success mt-3">
                                            <i class="fas fa-check-circle"></i> Payment initiated to <?= $_SESSION['mpesa_phone'] ?>
                                            <br>Amount: Ksh <?= number_format($_SESSION['mpesa_amount'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Pay on Delivery Option -->
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cash_on_delivery" 
                                    <?= (isset($_SESSION['form_data']['payment_method']) && $_SESSION['form_data']['payment_method'] === 'cash_on_delivery') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cod">
                                    Pay on Delivery
                                </label>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Order Summary</h4>
                </div>
                <div class="card-body">
                    <?php 
                    $delivery_fee = ($_SESSION['form_data']['county'] ?? '') === 'Nairobi' ? 0 : 500;
                    $order_totals = calculate_cart_totals($delivery_fee);
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong>Ksh <?= number_format($order_totals['subtotal'], 2) ?></strong>
                    </div>
                    <?php if (($order_totals['total_discount'] ?? 0) > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Discount:</span>
                        <strong class="text-success">- Ksh <?= number_format($order_totals['total_discount'], 2) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery Fee:</span>
                        <strong>Ksh <?= number_format($order_totals['delivery_fee'], 2) ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold">Total:</span>
                        <strong class="text-primary fs-5">Ksh <?= number_format($order_totals['total_amount'], 2) ?></strong>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn btn-success w-100 py-3" id="completeOrderBtn">
                        <i class="fas fa-lock"></i> Complete Order
                    </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle payment method selection
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const mpesaDetails = document.getElementById('mpesa-details');
    const completeOrderBtn = document.getElementById('completeOrderBtn');
    
    function updatePaymentUI() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // Show/hide M-Pesa details
        mpesaDetails.style.display = selectedMethod === 'mpesa' ? 'block' : 'none';
        
        // Enable complete order button
        completeOrderBtn.disabled = false;
    }
    
    // Listen for payment method changes
    paymentMethods.forEach(method => {
        method.addEventListener('change', updatePaymentUI);
    });
    
    // Initialize UI
    updatePaymentUI();
    
    // Form submission handling
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            // Only validate M-Pesa initiation
            if (paymentMethod === 'mpesa') {
                const mpesaInitiated = <?= isset($_SESSION['mpesa_payment_initiated']) ? 'true' : 'false' ?>;
                if (!mpesaInitiated) {
                    e.preventDefault();
                    alert('Please initiate M-Pesa payment first by clicking the "Initiate M-Pesa Payment" button');
                    document.getElementById('mpesa_phone').focus();
                    return false;
                }
            }
            
            return true;
        });
    }
    
    // Update delivery fee when county changes
    document.getElementById('county').addEventListener('change', function() {
        // In a real implementation, you would AJAX call to recalculate totals
        const deliveryFeeElement = document.querySelector('.card-body div:nth-child(3) strong');
        if (this.value === 'Nairobi') {
            deliveryFeeElement.textContent = 'Ksh 0.00';
        } else {
            deliveryFeeElement.textContent = 'Ksh 500.00';
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>