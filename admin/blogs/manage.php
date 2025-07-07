<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = "You must be logged in to access this page";
    header("Location: login.php");
    exit();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$author = isset($_GET['author']) ? (int)$_GET['author'] : 0;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(b.title LIKE :search OR b.content LIKE :search OR b.excerpt LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status)) {
    if ($status === 'published') {
        $where[] = "b.published_at <= NOW()";
    } elseif ($status === 'draft') {
        $where[] = "b.published_at IS NULL OR b.published_at > NOW()";
    } elseif ($status === 'featured') {
        $where[] = "b.is_featured = 1";
    }
}

if ($category > 0) {
    $where[] = "b.category_id = :category";
    $params[':category'] = $category;
}

if ($author > 0) {
    $where[] = "b.author_id = :author";
    $params[':author'] = $author;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM blog_posts b $where_clause";
$count_stmt = $pdo->prepare($count_query);

foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}

$count_stmt->execute();
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Get blog posts with pagination
$query = "
    SELECT b.*, 
           c.name as category_name,
           u.username as author_name,
           (SELECT COUNT(*) FROM blog_comments WHERE blog_id = b.id) as comment_count
    FROM blog_posts b
    LEFT JOIN blog_categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.author_id = u.id
    $where_clause
    ORDER BY COALESCE(b.published_at, b.created_at) DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get data for filters
$categories = $pdo->query("SELECT id, name FROM blog_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$authors = $pdo->query("SELECT id, username FROM users WHERE role = 'admin' OR role = 'author' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Blog Posts</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="add.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> New Post
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="fas fa-filter me-2"></i> Filters
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Title or content..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="featured" <?= $status === 'featured' ? 'selected' : '' ?>>Featured</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="author" class="form-label">Author</label>
                            <select class="form-select" id="author" name="author">
                                <option value="0">All Authors</option>
                                <?php foreach ($authors as $auth): ?>
                                    <option value="<?= $auth['id'] ?>" <?= $author == $auth['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($auth['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                        
                        <div class="col-md-1 d-flex align-items-end">
                            <a href="manage.php" class="btn btn-outline-secondary w-100" title="Reset filters">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-newspaper me-2"></i> Posts 
                            <span class="badge bg-primary rounded-pill"><?= number_format($total) ?></span>
                        </div>
                        <div class="text-muted small">
                            Page <?= $page ?> of <?= $total_pages ?>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($blogs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <h4>No blog posts found</h4>
                            <p class="text-muted">Try adjusting your filters or create a new post</p>
                            <a href="add.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-1"></i> Create New Post
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30%">Title</th>
                                        <th width="15%">Author</th>
                                        <th width="15%">Category</th>
                                        <th width="15%">Status</th>
                                        <th width="15%">Date</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blogs as $blog): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">
                                                    <a href="edit.php?id=<?= $blog['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($blog['title']) ?>
                                                    </a>
                                                </div>
                                                <div class="small text-muted">
                                                    /<?= htmlspecialchars($blog['slug']) ?>
                                                    <?php if ($blog['comment_count'] > 0): ?>
                                                        <span class="ms-2">
                                                            <i class="far fa-comment"></i> <?= $blog['comment_count'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($blog['author_name']) ?></td>
                                            <td><?= $blog['category_name'] ? htmlspecialchars($blog['category_name']) : 'Uncategorized' ?></td>
                                            <td>
                                                <?php if ($blog['published_at'] && strtotime($blog['published_at']) <= time()): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i> Published
                                                    </span>
                                                    <?php if ($blog['is_featured']): ?>
                                                        <span class="badge bg-primary ms-1">
                                                            <i class="fas fa-star"></i> Featured
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-edit"></i> Draft
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($blog['published_at']): ?>
                                                    <?= date('M j, Y', strtotime($blog['published_at'])) ?>
                                                <?php else: ?>
                                                    <?= date('M j, Y', strtotime($blog['created_at'])) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit.php?id=<?= $blog['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Edit"
                                                       data-bs-toggle="tooltip">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?= $blog['id'] ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       title="Delete"
                                                       data-bs-toggle="tooltip"
                                                       onclick="return confirm('Are you sure you want to delete this post?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                    <a href="../blog-post.php?slug=<?= htmlspecialchars($blog['slug']) ?>" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       title="View"
                                                       data-bs-toggle="tooltip"
                                                       target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Blog posts pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php 
                                // Show limited pagination links
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                
                                if ($start > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                    if ($start > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; 
                                
                                if ($end < $total_pages) {
                                    if ($end < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
// Enable Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirm before bulk actions
    document.getElementById('bulkActionBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to perform this action on selected posts?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/admin-footer.php'; ?>