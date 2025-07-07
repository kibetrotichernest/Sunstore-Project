<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter by status
$status = isset($_GET['status']) ? $_GET['status'] : '';

$where = [];
$params = [];
$types = '';

if (!empty($status)) {
    if ($status === 'active') {
        $where[] = "is_active = 1";
    } elseif ($status === 'inactive') {
        $where[] = "is_active = 0";
    }
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM newsletter_subscribers $where_clause";
$count_stmt = $conn->prepare($count_query);

if ($types && $params) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_row()[0];
$count_stmt->close();

$total_pages = ceil($total / $limit);

// Get subscribers
$query = "SELECT * FROM newsletter_subscribers $where_clause ORDER BY subscribed_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if ($types && $params) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$subscribers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle subscriber actions (activate/deactivate/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    if ($action === 'activate') {
        $stmt = $conn->prepare("UPDATE newsletter_subscribers SET is_active = 1, unsubscribed_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($action === 'deactivate') {
        $stmt = $conn->prepare("UPDATE newsletter_subscribers SET is_active = 0, unsubscribed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        $stmt->bind_param("i", $id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Subscriber ' . $action . 'd successfully';
    } else {
        $_SESSION['error'] = 'Error performing action on subscriber';
    }
    $stmt->close();
    
    header('Location: view.php?' . http_build_query($_GET));
    exit;
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Newsletter Subscribers</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="export.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filters
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Subscribers</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="view.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Subscribed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No subscribers found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subscribers as $subscriber): ?>
                                <tr>
                                    <td><?= htmlspecialchars($subscriber['email']) ?></td>
                                    <td><?= htmlspecialchars($subscriber['name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $subscriber['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $subscriber['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($subscriber['subscribed_at'])) ?></td>
                                    <td>
                                        <?php if ($subscriber['is_active']): ?>
                                            <a href="view.php?<?= http_build_query(array_merge($_GET, ['action' => 'deactivate', 'id' => $subscriber['id']])) ?>" class="btn btn-sm btn-warning" title="Deactivate">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="view.php?<?= http_build_query(array_merge($_GET, ['action' => 'activate', 'id' => $subscriber['id']])) ?>" class="btn btn-sm btn-success" title="Activate">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="view.php?<?= http_build_query(array_merge($_GET, ['action' => 'delete', 'id' => $subscriber['id']])) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this subscriber?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>