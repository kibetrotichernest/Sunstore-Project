<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter by status
$status = isset($_GET['status']) ? $_GET['status'] : '';

$where = [];
$params = [];
$types = '';

if (!empty($status)) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM contact_inquiries $where_clause";
$count_stmt = $conn->prepare($count_query);

if ($types && $params) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_row()[0];
$count_stmt->close();

$total_pages = ceil($total / $limit);

// Get inquiries
$query = "SELECT * FROM contact_inquiries $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if ($types && $params) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle status update
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $new_status = $_GET['update_status'];
    
    $stmt = $conn->prepare("UPDATE contact_inquiries SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Inquiry status updated successfully';
    } else {
        $_SESSION['error'] = 'Error updating inquiry status';
    }
    $stmt->close();
    
    header('Location: contact.php?' . http_build_query($_GET));
    exit;
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Contact Form Submissions</h1>
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
                                <option value="">All Statuses</option>
                                <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>New</option>
                                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="contact.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inquiries)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No inquiries found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inquiries as $inquiry): ?>
                                <tr>
                                    <td><?= htmlspecialchars($inquiry['name']) ?></td>
                                    <td><?= htmlspecialchars($inquiry['email']) ?></td>
                                    <td><?= htmlspecialchars($inquiry['subject']) ?></td>
                                    <td><?= date('M j, Y', strtotime($inquiry['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $inquiry['status'] === 'new' ? 'warning' : 
                                            ($inquiry['status'] === 'in_progress' ? 'info' : 'success')
                                        ?>">
                                            <?= ucfirst(str_replace('_', ' ', $inquiry['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#inquiryModal<?= $inquiry['id'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i> Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="contact.php?<?= http_build_query(array_merge($_GET, ['update_status' => 'new', 'id' => $inquiry['id']])) ?>">
                                                        <span class="badge bg-warning me-2">New</span> Mark as New
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="contact.php?<?= http_build_query(array_merge($_GET, ['update_status' => 'in_progress', 'id' => $inquiry['id']])) ?>">
                                                        <span class="badge bg-info me-2">In Progress</span> Mark as In Progress
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="contact.php?<?= http_build_query(array_merge($_GET, ['update_status' => 'resolved', 'id' => $inquiry['id']])) ?>">
                                                        <span class="badge bg-success me-2">Resolved</span> Mark as Resolved
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Modal -->
                                <div class="modal fade" id="inquiryModal<?= $inquiry['id'] ?>" tabindex="-1" aria-labelledby="inquiryModalLabel<?= $inquiry['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="inquiryModalLabel<?= $inquiry['id'] ?>">
                                                    Inquiry from <?= htmlspecialchars($inquiry['name']) ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Name:</strong> <?= htmlspecialchars($inquiry['name']) ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Email:</strong> <?= htmlspecialchars($inquiry['email']) ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Phone:</strong> <?= htmlspecialchars($inquiry['phone']) ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Date:</strong> <?= date('M j, Y H:i', strtotime($inquiry['created_at'])) ?></p>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <p><strong>Subject:</strong> <?= htmlspecialchars($inquiry['subject']) ?></p>
                                                </div>
                                                <div class="mb-3">
                                                    <p><strong>Message:</strong></p>
                                                    <div class="border p-3 bg-light">
                                                        <?= nl2br(htmlspecialchars($inquiry['message'])) ?>
                                                    </div>
                                                </div>
                                                <?php if ($inquiry['notes']): ?>
                                                    <div class="mb-3">
                                                        <p><strong>Admin Notes:</strong></p>
                                                        <div class="border p-3 bg-light">
                                                            <?= nl2br(htmlspecialchars($inquiry['notes'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST" class="w-100">
                                                    <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                    <div class="mb-3">
                                                        <label for="notes<?= $inquiry['id'] ?>" class="form-label">Add Notes</label>
                                                        <textarea class="form-control" id="notes<?= $inquiry['id'] ?>" name="notes" rows="3"><?= htmlspecialchars($inquiry['notes']) ?></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Save Notes</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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