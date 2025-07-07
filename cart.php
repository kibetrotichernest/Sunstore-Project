<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = max(1, (int)$quantity);
            }
        }
        $_SESSION['success'] = "Cart updated successfully!";
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $_SESSION['success'] = "Item removed from cart!";
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle add to cart from direct request (if needed)
if (isset($_GET['add_to_cart']) && isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    $quantity = isset($_GET['quantity']) ? max(1, (int)$_GET['quantity']) : 1;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Get product details
    $product = get_product_by_id($product_id);
    
    if ($product) {
        if (isset($_SESSION['cart'][$product_id])) {
            // Update quantity if product already in cart
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image'],
                'discount' => $product['discount'] > 0 ? ($product['price'] * ($product['discount']/100)) * $quantity : 0
            ];
        }
        $_SESSION['success'] = "Product added to cart successfully!";
    } else {
        $_SESSION['error'] = "Failed to add product to cart. Please try again.";
    }
    header("Location: cart.php");
    exit();
}

// Calculate cart totals
$cart_totals = calculate_cart_totals();

$page_title = "Shopping Cart | " . htmlspecialchars(SITE_NAME);
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Your Shopping Cart</h4>
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
                    
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="alert alert-info">
                            Your cart is currently empty.
                        </div>
                        <a href="/sunstore-industries/product.php" class="btn btn-primary">
                            Continue Shopping
                        </a>
                    <?php else: ?>
                        <form method="POST" action="cart.php">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="/sunstore-industries/admin/assets/products/<?= htmlspecialchars($item['image']) ?>" class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <?= htmlspecialchars($item['name']) ?>
                                                        <?php if (!empty($item['discount'])): ?>
                                                            <br><small class="text-success">Discount: Ksh <?= number_format($item['discount'], 2) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Ksh <?= number_format($item['price'], 2) ?></td>
                                            <td>
                                                <input type="number" name="quantity[<?= $product_id ?>]" 
                                                       value="<?= $item['quantity'] ?>" min="1" class="form-control" style="width: 70px;">
                                            </td>
                                            <td>Ksh <?= number_format(($item['price'] * $item['quantity']) - ($item['discount'] ?? 0), 2) ?></td>
                                            <td>
                                                <button type="submit" name="remove_item" value="1" class="btn btn-sm btn-danger">
                                                    Remove
                                                </button>
                                                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between">
                                <a href="/sunstore-industries/products.php" class="btn btn-outline-primary">
                                    Continue Shopping
                                </a>
                                <div>
                                    <button type="submit" name="update_cart" value="1" class="btn btn-primary me-2">
                                        Update Cart
                                    </button>
                                    <a href="/sunstore-industries/checkout.php" class="btn btn-success">
                                        Proceed to Checkout
                                    </a>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
              <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
        <span>Subtotal:</span>
        <strong>Ksh <?= number_format($cart_totals['subtotal'] ?? 0, 2) ?></strong>
    </div>
    <?php if (($cart_totals['total_discount'] ?? 0) > 0): ?>
    <div class="d-flex justify-content-between mb-2">
        <span>Discount:</span>
        <strong class="text-success">- Ksh <?= number_format($cart_totals['total_discount'] ?? 0, 2) ?></strong>
    </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between mb-2">
        <span>Delivery Fee:</span>
        <strong>Ksh <?= number_format($cart_totals['delivery_fee'] ?? 0, 2) ?></strong>
    </div>
    <hr>
    <div class="d-flex justify-content-between mb-3">
        <span class="fw-bold">Total:</span>
        <strong class="text-primary fs-5">Ksh <?= number_format($cart_totals['total_amount'] ?? 0, 2) ?></strong>
    </div>
    
    <?php if (!empty($_SESSION['cart'])): ?>
        <a href="/sunstore-industries/checkout.php" class="btn btn-success w-100 py-2">
            Proceed to Checkout
        </a>
    <?php endif; ?>
</div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>