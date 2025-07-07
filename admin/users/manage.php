<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only admin can access this page
if ($_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if (!empty($role)) {
    $where[] = "role = ?";
    $params[] = $role;
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM admin_users $where_clause";
$count_stmt = $conn->prepare($count_query);

if ($types && $params) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_row()[0];
$count_stmt->close();

$total_pages = ceil($total / $limit);

// Get admin users
$query = "SELECT * FROM admin_users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if ($types && $params) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle user actions (activate/deactivate/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    // Prevent modifying the current user
    if ($id == $_SESSION['admin_id']) {
        $_SESSION['error'] = 'You cannot modify your own account from here';
    } else {
        if ($action === 'activate') {
            $stmt = $conn->prepare("UPDATE admin_users SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
        } elseif ($action === 'deactivate') {
            $stmt = $conn->prepare("UPDATE admin_users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'User ' . $action . 'd successfully';
        } else {
            $_SESSION['error'] = 'Error performing action on user';
        }
        $stmt->close();
    }
    
    header('Location: manage.php?' . http_build_query($_GET));
    exit;
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Admin Users</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add New
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
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="role">
                                <option value="">All Roles</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
                                <option value="viewer" <?= $role === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="manage.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'editor' ? 'primary' : 'secondary')
                                        ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $user['last_login'] ? date('M j, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['is_active']): ?>
                                            <a href="manage.php?<?= http_build_query(array_merge($_GET, ['action' => 'deactivate', 'id' => $user['id']])) ?>" class="btn btn-sm btn-warning" title="Deactivate">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="manage.php?<?= http_build_query(array_merge($_GET, ['action' => 'activate', 'id' => $user['id']])) ?>" class="btn btn-sm btn-success" title="Activate">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="manage.php?<?= http_build_query(array_merge($_GET, ['action' => 'delete', 'id' => $user['id']])) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
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