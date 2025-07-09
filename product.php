<?php
// Start session and process requests BEFORE any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Handle add to cart before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Check if user is logged in
    if (!isset($_SESSION['customer_id'])) {
        $_SESSION['error'] = "Please login to add products to cart";
        header("Location: customer_login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

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
                'discount' => $product['discount']
            ];
        }
        $_SESSION['success'] = "Product added to cart successfully!";
        header("Location: cart.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to add product to cart. Please try again.";
        header("Location: product.php?id=" . $product_id);
        exit();
    }
}

// Check if viewing a single product or all products
if (isset($_GET['id'])) {
    // Single product view
    $product_id = (int)$_GET['id'];
    $product = get_product_by_id($product_id);

    if (!$product) {
        $_SESSION['error'] = "Product not found";
        header("Location: product.php?view=all");
        exit();
    }

    // Set page title
    $page_title = htmlspecialchars($product['name']) . " | Sunstore Industries";

    // Get related products (if function exists)
    $related_products = function_exists('get_related_products') ? get_related_products($product['category_id'], $product_id, 4) : [];
} elseif (isset($_GET['brand']) && $_GET['brand'] !== 'all') {
    // Brand filter view
    $brand_id = (int)$_GET['brand'];
    $all_products = get_filtered_products(null, $brand_id);
    $page_title = "Products by Brand | Sunstore Industries";
} elseif (isset($_GET['view']) && $_GET['view'] === 'all') {
    // All products view
    $all_products = get_all_products();
    $page_title = "All Products | Sunstore Industries";
} else {
    // No valid parameters provided - default to all products
    header("Location: product.php?view=all");
    exit();
}

// Now include header after all potential redirects
require_once 'includes/header.php';
?>
<style>
    /* Custom Add to Cart Button */
    .btn-custom-addtocart {
        background-color: white;
        color: #997346;
        border: 1px solid #997346;
        transition: all 0.3s ease;
    }

    .btn-custom-addtocart:hover {
        background-color: #997346;
        color: white;
        border-color: #997346;
    }

    /* WhatsApp Button */
    .btn-success {
        background-color: #25D366;
        border-color: #25D366;
    }

    .btn-success:hover {
        background-color: #128C7E;
        border-color: #128C7E;
    }

    /* View Details Button */
    .btn-outline-primary {
        color: #134327;
        border-color: #134327;
    }

    .btn-outline-primary:hover {
        background-color: #134327;
        color: white;
    }

    /* Product Card Styling */
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .discount-badge {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
    }

    /* Image Gallery Styles */
    .product-thumbnails .img-thumbnail {
        transition: all 0.3s ease;
        border-width: 2px;
    }

    .product-thumbnails .img-thumbnail:hover {
        transform: scale(1.05);
    }

    .product-thumbnails .img-thumbnail.active-thumbnail {
        border-color: #134327 !important;
        opacity: 1 !important;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .thumbnail-item {
        margin: 0 5px 5px 0;
    }
</style>
<!-- Main Content -->
<div class="container my-5">
    <?php if (isset($_GET['id']) && $product): ?>
        <!-- Single Product View -->
        <div class="row">
            <!-- Product Images Column -->
            <div class="col-md-6">
                <div class="card mb-4 position-relative">
                    <!-- Main Product Image -->
                    <div class="product-main-image text-center p-3">
                        <img id="main-product-image" src="/Sunstore-Project/admin/assets/products/<?= htmlspecialchars($product['image']) ?>"
                            class="img-fluid" alt="<?= htmlspecialchars($product['name']) ?>" style="max-height: 400px;">
                        <?php if ($product['discount'] > 0): ?>
                            <div class="position-absolute top-0 end-0 m-2 discount-badge">
                                <?= $product['discount'] ?>% OFF
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Thumbnails -->
                    <?php
                    $additional_images = !empty($product['additional_images']) ? json_decode($product['additional_images'], true) : [];
                    if (!empty($additional_images)): ?>
                        <div class="product-thumbnails mt-3 p-3">
                            <div class="d-flex flex-wrap">
                                <!-- Main image thumbnail -->
                                <div class="thumbnail-item">
                                    <img src="/Sunstore-Project/admin/assets/products/<?= htmlspecialchars($product['image']) ?>"
                                        class="img-thumbnail cursor-pointer active-thumbnail"
                                        style="width: 80px; height: 80px; object-fit: cover;"
                                        onclick="changeMainImage(this.src, this)"
                                        alt="Thumbnail 1">
                                </div>

                                <!-- Additional images thumbnails -->
                                <?php foreach ($additional_images as $index => $image): ?>
                                    <div class="thumbnail-item">
                                        <img src="/Sunstore-Project/admin/assets/products/<?= htmlspecialchars($image) ?>"
                                            class="img-thumbnail cursor-pointer"
                                            style="width: 80px; height: 80px; object-fit: cover; opacity: 0.7;"
                                            onclick="changeMainImage(this.src, this)"
                                            alt="Thumbnail <?= $index + 2 ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Details Column -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title"><?= htmlspecialchars($product['name']) ?></h2>

                        <!-- Price Display -->
                        <div class="mb-3">
                            <?php if ($product['discount'] > 0): ?>
                                <?php $discounted_price = $product['price'] * (1 - ($product['discount'] / 100)); ?>
                                <h4 class="text-danger fw-bold">Ksh <?= number_format($discounted_price, 2) ?></h4>
                                <h5 class="text-decoration-line-through text-muted">Ksh <?= number_format($product['price'], 2) ?></h5>
                            <?php else: ?>
                                <h4 class="text-danger fw-bold">Ksh <?= number_format($product['price'], 2) ?></h4>
                            <?php endif; ?>
                        </div>

                        <!-- Product Description -->
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="card-text"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>

                        <!-- Product Specifications -->
                        <?php if (!empty($product['specifications'])) : ?>
                            <div class="mb-4">
                                <h5>Specifications</h5>
                                <ul class="list-group list-group-flush">
                                    <?php
                                    $specs = json_decode($product['specifications'], true);
                                    if (is_array($specs)) {
                                        foreach ($specs as $key => $value) {
                                            // Handle both string and array values
                                            $display_key = is_string($key) ? $key : '';
                                            $display_value = is_string($value) ? $value : (is_array($value) ? implode(', ', $value) : '');
                                    ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="fw-bold"><?= htmlspecialchars($display_key) ?></span>
                                                <span><?= htmlspecialchars($display_value) ?></span>
                                            </li>
                                    <?php
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Add to Cart Form -->
                        <form method="post" action="product.php?id=<?= $product_id ?>">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <!-- Add to Cart Button -->
                                <button type="submit" name="add_to_cart" class="btn btn-custom-addtocart">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>

                                <!-- WhatsApp Inquiry Button -->
                                <a href="https://wa.me/+254743392675?text=<?= urlencode("I'm interested in: " . $product['name'] . " (Product ID: " . $product_id . ") - " . SITE_URL . "/product.php?id=" . $product_id) ?>"
                                    class="btn btn-success" target="_blank">
                                    <i class="fab fa-whatsapp"></i> WhatsApp Inquiry
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="mb-4">Related Products</h3>
                </div>

                <?php foreach ($related_products as $related): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card product-card h-100">
                            <?php if ($related['discount'] > 0): ?>
                                <div class="position-absolute top-0 end-0 m-2 discount-badge">
                                    <?= $related['discount'] ?>% OFF
                                </div>
                            <?php endif; ?>

                            <img src="/Sunstore-Project/admin/assets/products/<?= $related['image'] ?>"
                                class="card-img-top p-3" alt="<?= htmlspecialchars($related['name']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>

                                <?php if ($related['discount'] > 0): ?>
                                    <?php $discounted_price = $related['price'] * (1 - ($related['discount'] / 100)); ?>
                                    <p class="card-text text-danger fw-bold">Ksh <?= number_format($discounted_price, 2) ?></p>
                                    <p class="card-text text-decoration-line-through text-muted">Ksh <?= number_format($related['price'], 2) ?></p>
                                <?php else: ?>
                                    <p class="card-text text-danger fw-bold">Ksh <?= number_format($related['price'], 2) ?></p>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <a href="product.php?id=<?= $related['id'] ?>" class="btn btn-outline-primary">View Details</a>
                                    <form method="post" action="product.php" class="d-grid">
                                        <input type="hidden" name="product_id" value="<?= $related['id'] ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-custom-addtocart">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </form>
                                    <a href="https://wa.me/+254743392675?text=<?= urlencode("I'm interested in: " . $related['name'] . " (Product ID: " . $related['id'] . ") - " . SITE_URL . "/product.php?id=" . $related['id']) ?>"
                                        class="btn btn-outline-success" target="_blank">
                                        <i class="fab fa-whatsapp"></i> Inquire
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- All Products View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>All Products</h2>
            <div>
                <a href="category.php" class="btn btn-outline-primary">Browse by Category</a>
            </div>
        </div>

        <div class="row">
            <?php if (!empty($all_products)): ?>
                <?php foreach ($all_products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card h-100">
                            <?php if ($product['discount'] > 0): ?>
                                <div class="position-absolute top-0 end-0 m-2 discount-badge">
                                    <?= $product['discount'] ?>% OFF
                                </div>
                            <?php endif; ?>

                            <img src="/Sunstore-Project/admin/assets/products/<?= $product['image'] ?>"
                                class="card-img-top p-3" alt="<?= htmlspecialchars($product['name']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>

                                <?php if ($product['discount'] > 0): ?>
                                    <?php $discounted_price = $product['price'] * (1 - ($product['discount'] / 100)); ?>
                                    <p class="card-text text-danger fw-bold">Ksh <?= number_format($discounted_price, 2) ?></p>
                                    <p class="card-text text-decoration-line-through text-muted">Ksh <?= number_format($product['price'], 2) ?></p>
                                <?php else: ?>
                                    <p class="card-text text-danger fw-bold">Ksh <?= number_format($product['price'], 2) ?></p>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">View Details</a>
                                    <form method="post" action="product.php" class="d-grid">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-custom-addtocart">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </form>
                                    <a href="https://wa.me/+254743392675?text=<?= urlencode("I'm interested in: " . $product['name'] . " (Product ID: " . $product['id'] . ") - " . SITE_URL . "/product.php?id=" . $product['id']) ?>"
                                        class="btn btn-outline-success" target="_blank">
                                        <i class="fab fa-whatsapp"></i> WhatsApp Inquiry
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No products found.</div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Image Gallery JavaScript -->
<script>
    function changeMainImage(newSrc, clickedElement) {
        // Update main image
        document.getElementById('main-product-image').src = newSrc;

        // Update active thumbnail styling
        document.querySelectorAll('.product-thumbnails .img-thumbnail').forEach(img => {
            img.classList.remove('active-thumbnail');
            img.style.opacity = '0.7';
        });

        clickedElement.classList.add('active-thumbnail');
        clickedElement.style.opacity = '1';
    }
</script>

<?php
require_once 'includes/footer.php';
?>
