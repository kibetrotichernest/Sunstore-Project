<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category details
$category = get_category_by_id($category_id);


// Set page title
$page_title = htmlspecialchars($category['name']) . " - Sunstore Industries";

// Get pagination parameters
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Get category data
$total_products = count_products_in_category($category_id);
$total_pages = ceil($total_products / $per_page);
$products = get_products_by_category($category_id, $current_page, $per_page);
$subcategories = get_subcategories($category_id);

// Now include header after all database operations
require_once 'includes/header.php';
?>

<!-- Main Content -->
<div class="container my-4">
    <!-- Category Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="product.php">Products</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($category['name']) ?></li>
                </ol>
            </nav>

            <div class="category-header bg-light p-4 rounded">
                <h1 class="mb-3"><?= htmlspecialchars($category['name']) ?></h1>
                <?php if (!empty($category['description'])): ?>
                    <p class="lead"><?= htmlspecialchars($category['description']) ?></p>
                <?php endif; ?>
                <?php if (!empty($category['image'])): ?>
                    <img src="/Sunstore-Project/admin/assets/categories/<?= $category['image'] ?>" class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($category['name']) ?>" style="max-height: 300px;">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <!-- Subcategories -->
            <?php if (!empty($subcategories)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Subcategories</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($subcategories as $subcategory): ?>
                                <li class="list-group-item">
                                    <a href="category.php?id=<?= $subcategory['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($subcategory['name']) ?>
                                        <span class="badge bg-primary rounded-pill float-end"><?= $subcategory['product_count'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Filter Products</h5>
                </div>
                <div class="card-body">
                    <!-- Price Filter -->
                    <h6 class="mt-3">Price Range</h6>
                    <form id="price-filter-form" action="category.php?id=<?= $category_id ?>" method="get">
                        <input type="hidden" name="id" value="<?= $category_id ?>">

                        <div class="form-check">
                            <input class="form-check-input price-filter" type="radio" name="price" id="price-all" value="all" checked>
                            <label class="form-check-label" for="price-all">All Prices</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input price-filter" type="radio" name="price" id="price1" value="0-10000">
                            <label class="form-check-label" for="price1">Ksh 0 - Ksh 10,000</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input price-filter" type="radio" name="price" id="price2" value="10000-50000">
                            <label class="form-check-label" for="price2">Ksh 10,000 - Ksh 50,000</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input price-filter" type="radio" name="price" id="price3" value="50000-100000">
                            <label class="form-check-label" for="price3">Ksh 50,000 - Ksh 100,000</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input price-filter" type="radio" name="price" id="price4" value="100000-">
                            <label class="form-check-label" for="price4">Above Ksh 100,000</label>
                        </div>
                    </form>

                    <!-- Brand Filter -->
                    <h6 class="mt-4">Brands</h6>
                    <form id="brand-filter-form" action="category.php?id=<?= $category_id ?>" method="get">
                        <input type="hidden" name="id" value="<?= $category_id ?>">

                        <select class="form-select" name="brand" onchange="this.form.submit()">
                            <option value="all">All Brands</option>
                            <?php foreach (get_brands_by_category($category_id) as $brand): ?>
                                <option value="<?= $brand['id'] ?>">
                                    <?= htmlspecialchars($brand['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9">
            <!-- Sorting Options -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><?= $total_products ?> Products in <?= htmlspecialchars($category['name']) ?></h4>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Sort By: Default
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item" href="?id=<?= $category_id ?>&sort=default">Default</a></li>
                        <li><a class="dropdown-item" href="?id=<?= $category_id ?>&sort=price_low_to_high">Price: Low to High</a></li>
                        <li><a class="dropdown-item" href="?id=<?= $category_id ?>&sort=price_high_to_low">Price: High to Low</a></li>
                        <li><a class="dropdown-item" href="?id=<?= $category_id ?>&sort=name_a_to_z">Name: A to Z</a></li>
                        <li><a class="dropdown-item" href="?id=<?= $category_id ?>&sort=name_z_to_a">Name: Z to A</a></li>
                        <li><a class="dropdown-item" href="?id=<?= $category_id ?>&sort=newest">Newest First</a></li>
                    </ul>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card product-card h-100">
                                <?php if ($product['discount'] > 0): ?>
                                    <div class="position-absolute top-0 end-0 m-2 discount-badge">
                                        <?= $product['discount'] ?>% OFF
                                    </div>
                                <?php endif; ?>

                                <img src="/Sunstore-Project/admin/assets/products/<?= $product['image'] ?>" class="card-img-top p-3" alt="<?= htmlspecialchars($product['name']) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text text-muted"><?= htmlspecialchars($product['short_description']) ?></p>

                                    <?php if ($product['discount'] > 0): ?>
                                        <?php $discounted_price = $product['price'] * (1 - ($product['discount'] / 100)); ?>
                                        <p class="card-text text-danger fw-bold">Ksh <?= number_format($discounted_price, 2) ?></p>
                                        <p class="card-text text-decoration-line-through text-muted">Ksh <?= number_format($product['price'], 2) ?></p>
                                    <?php else: ?>
                                        <p class="card-text text-danger fw-bold">Ksh <?= number_format($product['price'], 2) ?></p>
                                    <?php endif; ?>

                                    <div class="d-grid gap-2">
                                        <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a>
                                        <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-outline-primary">Add to Cart</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center">
                            <h5>No products found in this category</h5>
                            <p>Try browsing our <a href="product.php?view=all">other categories</a></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $category_id ?>&page=<?= $current_page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?id=<?= $category_id ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $category_id ?>&page=<?= $current_page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
