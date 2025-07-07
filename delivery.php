<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session at the very beginning
session_start();

$page_title = "Delivery Information - Sunstore Industries";
require_once 'includes/header.php';

// Get customer info if logged in
$customer_data = [];
if (isset($_SESSION['customer_id'])) {  // Removed extra parenthesis here
    $customer_id = $_SESSION['customer_id'];
    try {
        $conn = db_connect();
        $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer_data = $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching customer data: " . $e->getMessage());
    }
}

// Get delivery zones and rates from database
try {
    $conn = db_connect();
    
    // Get delivery zones
    $zones_stmt = $conn->query("SELECT * FROM delivery_zones ORDER BY zone_name");
    $delivery_zones = $zones_stmt->fetch_all(MYSQLI_ASSOC);
    
    // Get delivery methods
    $methods_stmt = $conn->query("SELECT * FROM delivery_methods WHERE is_active = 1 ORDER BY delivery_time");
    $delivery_methods = $methods_stmt->fetch_all(MYSQLI_ASSOC);
    
    // Get FAQs
    $faq_stmt = $conn->query("SELECT * FROM delivery_faqs ORDER BY display_order");
    $delivery_faqs = $faq_stmt->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Error loading delivery info: " . $e->getMessage());
    // Fallback data
    $delivery_zones = [
        ['zone_name' => 'Nairobi', 'delivery_time' => '1-2 business days', 'rate' => 500],
        ['zone_name' => 'Other Major Towns', 'delivery_time' => '2-3 business days', 'rate' => 800],
        ['zone_name' => 'Remote Areas', 'delivery_time' => '3-5 business days', 'rate' => 1200]
    ];
    
    $delivery_methods = [
        ['method_name' => 'Standard Delivery', 'delivery_time' => '2-3 business days', 'rate' => 0, 'description' => 'Our most economical option'],
        ['method_name' => 'Express Delivery', 'delivery_time' => '1 business day', 'rate' => 500, 'description' => 'Priority handling for urgent orders'],
        ['method_name' => 'Installation Team Delivery', 'delivery_time' => 'Scheduled appointment', 'rate' => 1500, 'description' => 'Includes professional installation']
    ];
    
    $delivery_faqs = [
        ['question' => 'How long does delivery take?', 'answer' => 'Typically 1-3 business days depending on your location.'],
        ['question' => 'Do you offer installation?', 'answer' => 'Yes, for solar systems we provide professional installation.'],
        ['question' => 'Can I track my order?', 'answer' => 'Yes, you will receive tracking information once shipped.']
    ];
}
?>

<div class="delivery-page py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-5 fw-bold text-primary mb-3">Delivery Information</h1>
                <p class="lead">We deliver solar products across Kenya with professional installation options</p>
            </div>
        </div>

        <!-- Customer Delivery Information -->
        <?php if (!empty($customer_data)): ?>
        <section class="customer-delivery-info mb-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="fw-bold mb-4">Your Default Delivery Information</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Full Name</label>
                                <p class="fw-bold"><?= htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Phone Number</label>
                                <p class="fw-bold"><?= htmlspecialchars($customer_data['phone']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Delivery Address</label>
                                <p class="fw-bold">
                                    <?= htmlspecialchars($customer_data['address']) ?><br>
                                    <?= htmlspecialchars($customer_data['city']) ?><br>
                                    <?= htmlspecialchars($customer_data['county']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        This information will be used as your default delivery location. 
                        You can update it in <a href="account.php">your account settings</a> or 
                        change it during checkout.
                    </div>
                </div>
            </div>
        </section>
        <?php else: ?>
        <section class="customer-delivery-info mb-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="fw-bold mb-4">Delivery Information</h2>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        You're not logged in. <a href="login.php?redirect=delivery.php">Sign in</a> to see your saved delivery information 
                        or provide your details during checkout.
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Delivery Tracking Form -->
        <section class="tracking-form mb-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="fw-bold mb-4">Track Your Order</h2>
                    <form action="track-order.php" method="GET" class="row g-3">
                        <div class="col-md-8">
                            <label for="tracking_number" class="form-label">Enter Tracking Number</label>
                            <input type="text" class="form-control form-control-lg" id="tracking_number" 
                                   name="tracking_number" placeholder="e.g. SSI-123456" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Track Order</button>
                        </div>
                    </form>
                    <p class="text-muted mt-2">Can't find your tracking number? <a href="contact.php">Contact us</a></p>
                </div>
            </div>
        </section>

        <!-- Delivery Zones & Rates -->
        <section class="delivery-zones mb-5">
            <h2 class="fw-bold mb-4">Delivery Zones & Rates</h2>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Zone</th>
                            <th>Delivery Time</th>
                            <th>Rate (KES)</th>
                            <th>Coverage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delivery_zones as $zone): ?>
                        <tr>
                            <td><?= htmlspecialchars($zone['zone_name']) ?></td>
                            <td><?= htmlspecialchars($zone['delivery_time']) ?></td>
                            <td><?= number_format($zone['rate']) ?></td>
                            <td><?= htmlspecialchars($zone['coverage'] ?? 'Major areas') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted mt-2">* Rates are for standard delivery. Express options available.</p>
        </section>

        <!-- Delivery Methods -->
        <section class="delivery-methods mb-5">
            <h2 class="fw-bold mb-4">Delivery Options</h2>
            <div class="row g-4">
                <?php foreach ($delivery_methods as $method): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-3"><?= htmlspecialchars($method['method_name']) ?></h4>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Delivery Time:</span>
                                <strong><?= htmlspecialchars($method['delivery_time']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Cost:</span>
                                <strong>KES <?= number_format($method['rate']) ?></strong>
                            </div>
                            <p><?= htmlspecialchars($method['description']) ?></p>
                            <?php if ($method['rate'] > 0): ?>
                            <div class="badge bg-warning text-dark">Premium Service</div>
                            <?php else: ?>
                            <div class="badge bg-success">Free</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Delivery Process -->
        <section class="delivery-process mb-5">
            <h2 class="fw-bold mb-4">Our Delivery Process</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="process-step text-center">
                        <div class="step-number bg-primary text-white rounded-circle mx-auto mb-3">1</div>
                        <h5>Order Confirmation</h5>
                        <p>You'll receive an email with order details</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="process-step text-center">
                        <div class="step-number bg-primary text-white rounded-circle mx-auto mb-3">2</div>
                        <h5>Processing</h5>
                        <p>We prepare your items for shipment</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="process-step text-center">
                        <div class="step-number bg-primary text-white rounded-circle mx-auto mb-3">3</div>
                        <h5>Dispatch</h5>
                        <p>Your order leaves our warehouse</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="process-step text-center">
                        <div class="step-number bg-primary text-white rounded-circle mx-auto mb-3">4</div>
                        <h5>Delivery</h5>
                        <p>Items arrive at your location</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Delivery FAQs -->
        <section class="delivery-faq mb-5">
            <h2 class="fw-bold mb-4">Delivery FAQs</h2>
            <div class="accordion" id="deliveryAccordion">
                <?php foreach ($delivery_faqs as $index => $faq): ?>
                <div class="accordion-item">
                    <h3 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" 
                                type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?= $index ?>" 
                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" 
                                aria-controls="collapse<?= $index ?>">
                            <?= htmlspecialchars($faq['question']) ?>
                        </button>
                    </h3>
                    <div id="collapse<?= $index ?>" 
                         class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                         aria-labelledby="heading<?= $index ?>" 
                         data-bs-parent="#deliveryAccordion">
                        <div class="accordion-body">
                            <?= htmlspecialchars($faq['answer']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Delivery Policy -->
        <section class="delivery-policy">
            <h2 class="fw-bold mb-4">Delivery Policy</h2>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item border-0">All deliveries are made within the estimated time frames provided at checkout.</li>
                        <li class="list-group-item border-0">Solar system deliveries include a phone consultation before installation.</li>
                        <li class="list-group-item border-0">Someone must be available to receive and sign for the delivery.</li>
                        <li class="list-group-item border-0">Additional fees may apply for remote locations or special requirements.</li>
                        <li class="list-group-item border-0">Delivery times may be affected by weather conditions or unforeseen circumstances.</li>
                    </ol>
                    <div class="alert alert-info mt-4">
                        <strong>Need help?</strong> Contact our delivery team at 
                        <a href="mailto:delivery@sunstoreindustries.com">delivery@sunstoreindustries.com</a> 
                        or call <a href="tel:+254700123456">0700 123 456</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<style>
    .delivery-page {
        background-color: #f8f9fa;
    }
    .step-number {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
    }
    .process-step {
        background: white;
        padding: 20px;
        border-radius: 8px;
        height: 100%;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .accordion-button:not(.collapsed) {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
</style>

<?php
require_once 'includes/footer.php';
?>