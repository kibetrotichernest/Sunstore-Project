<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter and status
$status = isset($_GET['status']) ? $_GET['status'] : '';
$blog_id = isset($_GET['blog_id']) ? (int)$_GET['blog_id'] : 0;

// Build WHERE clause for filtering
$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "c.is_approved = :is_approved";
    $params[':is_approved'] = $status === 'approved' ? 1 : 0;
}

if ($blog_id > 0) {
    $where[] = "c.blog_id = :blog_id";
    $params[':blog_id'] = $blog_id;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM blog_comments c $where_clause";
$count_stmt = $pdo->prepare($count_query);

foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}

$count_stmt->execute();
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Get comments with pagination
$query = "
    SELECT c.*, b.title as blog_title, b.slug as blog_slug, 
           IFNULL(u.username, c.name) as commenter_name, 
           IFNULL(u.email, c.email) as commenter_email
    FROM blog_comments c
    LEFT JOIN blog_posts b ON c.blog_id = b.id
    LEFT JOIN users u ON c.user_id = u.id
    $where_clause
    ORDER BY c.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get blogs for filter dropdown
$blogs = $pdo->query("SELECT id, title FROM blog_posts ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Handle comment actions (approve/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE blog_comments SET is_approved = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = 'Comment approved successfully';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM blog_comments WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = 'Comment deleted successfully';
        }
        
        // Remove action parameters and redirect
        $query_params = $_GET;
        unset($query_params['action']);
        unset($query_params['id']);
        header('Location: comments.php?' . http_build_query($query_params));
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Blog Comments</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="comments.php?status=pending" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-clock"></i> Pending
                        </a>
                        <a href="comments.php?status=approved" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-check"></i> Approved
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="fas fa-filter me-2"></i>Filters
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="blog_id" class="form-label">Blog Post</label>
                            <select class="form-select" id="blog_id" name="blog_id">
                                <option value="">All Blog Posts</option>
                                <?php foreach ($blogs as $blog): ?>
                                    <option value="<?= $blog['id'] ?>" <?= $blog_id == $blog['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($blog['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="comments.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync-alt me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <i class="fas fa-comments me-2"></i>Comments (<?= number_format($total) ?>)
                </div>
                <div class="card-body p-0">
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <h4>No comments found</h4>
                            <p class="text-muted">Try adjusting your filters</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30%">Comment</th>
                                        <th width="20%">Blog Post</th>
                                        <th width="15%">Commenter</th>
                                        <th width="15%">Date</th>
                                        <th width="10%">Status</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comments as $comment): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($comment['comment']) ?></div>
                                                <small class="text-muted">
                                                    <?= date('M j, Y \a\t H:i', strtotime($comment['created_at'])) ?>
                                                    <?php if ($comment['user_id']): ?>
                                                        <span class="badge bg-info ms-2">Registered User</span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <a href="../blog-post.php?slug=<?= htmlspecialchars($comment['blog_slug']) ?>" target="_blank" class="text-decoration-none">
                                                    <?= htmlspecialchars($comment['blog_title']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($comment['commenter_name']) ?>
                                                <div class="text-muted small"><?= htmlspecialchars($comment['commenter_email']) ?></div>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($comment['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $comment['is_approved'] ? 'success' : 'warning' ?>">
                                                    <?= $comment['is_approved'] ? 'Approved' : 'Pending' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (!$comment['is_approved']): ?>
                                                        <a href="comments.php?<?= http_build_query(array_merge($_GET, ['action' => 'approve', 'id' => $comment['id']])) ?>" 
                                                           class="btn btn-sm btn-outline-success" 
                                                           title="Approve"
                                                           data-bs-toggle="tooltip">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="comments.php?<?= http_build_query(array_merge($_GET, ['action' => 'delete', 'id' => $comment['id']])) ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       title="Delete"
                                                       data-bs-toggle="tooltip"
                                                       onclick="return confirm('Are you sure you want to delete this comment?')">
                                                        <i class="fas fa-trash-alt"></i>
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
                        <nav aria-label="Comments pagination">
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
    
    // Confirm before approving all pending comments
    document.getElementById('approveAllBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to approve all pending comments?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/admin-footer.php'; ?>