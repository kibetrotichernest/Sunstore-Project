<?php
// Redirect if not authenticated - MUST be before any output
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Set default title if not defined
if (!isset($page_title)) {
    $page_title = 'Admin Panel';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | <?= SITE_NAME ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico">
</head>
<body class="admin-body">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <button class="navbar-toggler me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand me-auto" href="index.php">
                <img src="../assets/images/logo-white.png" height="30" class="d-inline-block align-top" alt="<?= SITE_NAME ?>">
            </a>
            
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                    <img src="../assets/images/admin-avatar.png" alt="Admin" width="32" height="32" class="rounded-circle me-2">
                    <strong><?= $_SESSION['admin_name'] ?? 'Admin' ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                    <li><a class="dropdown-item" href="users/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../" target="_blank"><i class="fas fa-external-link-alt me-2"></i> View Site</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse" id="sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/sunstore-industries/admin/index.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/products/view.php">
                                <i class="fas fa-box-open me-2"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/projects/view.php">
                              <i class="fas fa-solar-panel me-2"></i>
                                                          Projects
                             </a>
                         </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/categories/manage.php">
                                <i class="fas fa-tags me-2"></i> Categories
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/orders/view.php">
                                <i class="fas fa-shopping-cart me-2"></i> Orders
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/blogs/manage.php">
                                <i class="fas fa-blog me-2"></i> Blog Posts
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/inquiries/contact.php">
                                <i class="fas fa-envelope me-2"></i> Inquiries
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/subscribers/view.php">
                                <i class="fas fa-users me-2"></i> Subscribers
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['admin_role'] === 'superadmin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/users/manage.php">
                                <i class="fas fa-user-shield me-2"></i> Admin Users
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/sunstore-industries/admin/reports/sales.php">
                                <i class="fas fa-chart-line me-2"></i> Reports
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <hr class="border-secondary my-3">
                    
                    <div class="px-3">
                        <div class="alert alert-info py-2 mb-0 small">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>System Status:</strong> Online
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2"><?= $page_title ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (isset($page_actions)): ?>
                            <?php foreach ($page_actions as $action): ?>
                                <a href="<?= $action['url'] ?>" class="btn btn-sm btn-<?= $action['type'] ?> ms-2">
                                    <i class="fas fa-<?= $action['icon'] ?> me-1"></i> <?= $action['text'] ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>