<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'functions.php';

// Check if user is properly logged in
$isLoggedIn = isset($_SESSION['customer_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Get full name if available
$customerName = '';
if ($isLoggedIn) {
    $customerName = htmlspecialchars($_SESSION['first_name']);
    if (!empty($_SESSION['last_name'])) {
        $customerName .= ' ' . htmlspecialchars($_SESSION['last_name']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/Sunstore-Project/assets/css/style.css">
</head>

<body>
    <!-- Top Navigation Bar -->
    <div class="top-navbar py-2 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span><i class="fas fa-envelope me-2"></i>info@sunstoreltd.com</span>
                    <span class="ms-3"><i class="fas fa-phone me-2"></i>+254743392675</span>
                </div>
                <div class="col-md-6 text-end">
                    <?php if ($isLoggedIn): ?>
                        <span class="me-2">Welcome, <?= $customerName ?></span>
                        <a href="/Sunstore-Project/customers/customer_account.php" class="text-white text-decoration-none me-3">My Account</a>
                        <a href="/Sunstore-Project/track_orders.php" class="text-white text-decoration-none me-3">My Orders</a>
                        <a href="/Sunstore-Project/blogs.php" class="text-white text-decoration-none me-3">Blogs</a>
                        <a href="/Sunstore-Project/about.php" class="text-white text-decoration-none me-3">About Us</a>
                    <?php else: ?>
                        <a href="/Sunstore-Project/about.php" class="text-white text-decoration-none me-3">About Us</a>
                        <a href="/Sunstore-Project/contact.php" class="text-white text-decoration-none me-3">Contact Us</a>
                        <a href="/Sunstore-Project/blogs.php" class="text-white text-decoration-none me-3">Blogs</a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
    <style>
        .responsive-logo {
            /* Base size for desktop */
            width: 200px;
            /* Fixed width */
            height: auto;
            /* Maintain aspect ratio */

            /* Smooth scaling */
            transition: all 0.3s ease;
        }

        /* Tablet */
        @media (max-width: 992px) {
            .responsive-logo {
                width: 180px;
            }
        }

        /* Large mobile */
        @media (max-width: 768px) {
            .responsive-logo {
                width: 160px;
            }
        }

        /* Small mobile */
        @media (max-width: 576px) {
            .responsive-logo {
                width: 140px;
            }
        }
    </style>
    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light py-3 sticky-top shadow-sm" style="background: linear-gradient(90deg, #997346, #f2c987);">
        <div class="container">
            <a class="navbar-brand" href="/Sunstore-Project/index.php">
                <img src="/Sunstore-Project/assets/images/logo.png"
                    alt="<?= htmlspecialchars(SITE_NAME ?? 'Sunstore Industries') ?>"
                    class="responsive-logo">
            </a>

            <div class="mx-3 flex-grow-1">
                <form class="d-flex" action="search.php" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search for solar products..." aria-label="Search">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="d-flex align-items-center">
                <?php if ($isLoggedIn): ?>
                    <div class="dropdown me-3">
                        <a class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" href="#" role="button" id="accountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <span><?= $customerName ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                            <li><a class="dropdown-item" href="/Sunstore-Project/customers/customer_account.php">
                                    <i class="fas fa-user me-2"></i> Profile
                                </a></li>
                            <li><a class="dropdown-item" href="/Sunstore-Project/customers/orders.php">
                                    <i class="fas fa-box-open me-2"></i> My Orders
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="/Sunstore-Project/customer_logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/Sunstore-Project/customer_login.php" class="btn btn-outline-primary me-3">
                        <i class="fas fa-sign-in-alt me-1"></i> Login/Register
                    </a>
                <?php endif; ?>

                <a href="/Sunstore-Project/cart.php" class="btn btn-outline-primary position-relative">
                    <i class="fas fa-shopping-cart me-1"></i> Cart
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Category Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark py-2" style="background-color: #134327;">
        <div class="container">
            <div class="dropdown">
                <button class="btn btn-dark dropdown-toggle" type="button" id="departmentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bars me-2"></i> Shop by Department
                </button>
                <ul class="dropdown-menu" aria-labelledby="departmentDropdown">
                    <?php
                    $categories = get_categories();
                    foreach ($categories as $category) {
                        echo '<li><a class="dropdown-item" href="/Sunstore-Project/category.php?id=' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</a></li>';
                    }
                    ?>
                </ul>
            </div>

            <div class="navbar-nav ms-auto">
                <?php
                $main_categories = get_main_categories();
                foreach ($main_categories as $main_cat) {
                    echo '<div class="nav-item dropdown">';
                    echo '<a class="nav-link dropdown-toggle" href="category.php?id=' . $main_cat['id'] . '" id="' . $main_cat['slug'] . 'Dropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
                    echo htmlspecialchars($main_cat['name']);
                    echo '</a>';

                    $sub_items = get_category_products($main_cat['id']);

                    if (!empty($sub_items)) {
                        echo '<div class="dropdown-menu mega-menu p-3" aria-labelledby="' . $main_cat['slug'] . 'Dropdown">';
                        echo '<div class="row">';

                        foreach ($sub_items as $item) {
                            echo '<div class="col-md-3 mb-3">';
                            echo '<div class="text-center">';
                            echo '<a href="product.php?id=' . $item['id'] . '" class="text-decoration-none">';
                            // echo '<img src="/sunstore-industries/assets/products/'.$item['image'].'" class="img-fluid mb-2" style="height: 100px; width: auto; object-fit: contain;">';
                            echo '<p class="mb-0 text-dark">' . htmlspecialchars($item['name']) . '</p>';
                            echo '</a>';
                            echo '</div>';
                            echo '</div>';
                        }

                        echo '</div>';
                        echo '</div>';
                    }

                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </nav>
