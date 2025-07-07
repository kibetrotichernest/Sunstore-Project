<?php
$page_title = "Manage Products";
$page_actions = [
    ['url' => 'add.php', 'text' => 'Add Product', 'type' => 'primary', 'icon' => 'plus']
];
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($category > 0) {
    $where[] = "pc.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

if ($status === 'active') {
    $where[] = "p.status = 1";
} elseif ($status === 'inactive') {
    $where[] = "p.status = 0";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get products
$sql = "SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as categories
        FROM products p
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        LEFT JOIN categories c ON pc.category_id = c.id
        $where_clause
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT ?, ?";

// Prepare parameters for main query
$main_params = $params;
$main_types = $types;

// Add pagination parameters
$main_params[] = $offset;
$main_params[] = $per_page;
$main_types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($main_types)) {
    $stmt->bind_param($main_types, ...$main_params);
}
$stmt->execute();
$products = $stmt->get_result();

// Total count for pagination
$count_sql = "SELECT COUNT(DISTINCT p.id) as total 
              FROM products p
              LEFT JOIN product_categories pc ON p.id = pc.product_id
              $where_clause";
$count_stmt = $conn->prepare($count_sql);

// Bind only the search/filter parameters (not pagination)
if (!empty($types) && !empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$result = $count_stmt->get_result();
$total = $result ? $result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total / $per_page);

// Get categories for filter
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Product List</h5>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category === $cat['id'] ? 'selected' : '' ?>>
                                    <?= $cat['name'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if ($products->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Categories</th>
                                <th>Price</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                    <img src="../assets/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" width="50">
                                    <?php else: ?>
                                    <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= $product['categories'] ? htmlspecialchars($product['categories']) : 'Uncategorized' ?></td>
                                <td><?= CURRENCY . number_format($product['price'], 2) ?></td>
                                <td><?= $product['discount'] ?>%</td>
                                <td>
                                    <span class="badge bg-<?= $product['status'] ? 'success' : 'danger' ?>">
                                        <?= $product['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['featured']): ?>
                                    <span class="badge bg-warning text-dark">Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&status=<?= $status ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&status=<?= $status ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&status=<?= $status ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">No products found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>