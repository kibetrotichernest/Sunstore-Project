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
$count_query = "SELECT COUNT(*) FROM customer_questions $where_clause";
$count_stmt = $conn->prepare($count_query);

if ($types && $params) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_row()[0];
$count_stmt->close();

$total_pages = ceil($total / $limit);

// Get questions
$query = "SELECT q.*, u.name as user_name, u.email as user_email 
          FROM customer_questions q
          LEFT JOIN users u ON q.user_id = u.id
          $where_clause
          ORDER BY q.created_at DESC
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if ($types && $params) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $question_id = intval($_POST['question_id']);
    $answer = trim($_POST['answer']);
    
    $stmt = $conn->prepare("UPDATE customer_questions SET answer = ?, status = 'answered', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $answer, $question_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Answer submitted successfully';
        
        // Send email notification to customer
        $question = $conn->query("SELECT q.*, u.email FROM customer_questions q LEFT JOIN users u ON q.user_id = u.id WHERE q.id = $question_id")->fetch_assoc();
        if ($question) {
            $to = $question['email'] ? $question['email'] : $question['email'];
            $subject = "Response to your question";
            $message = "Dear " . htmlspecialchars($question['name']) . ",\n\n";
            $message .= "Thank you for your question:\n\n";
            $message .= htmlspecialchars($question['question']) . "\n\n";
            $message .= "Our response:\n\n";
            $message .= htmlspecialchars($answer) . "\n\n";
            $message .= "Best regards,\n";
            $message .= SITE_NAME;
            
            mail($to, $subject, $message);
        }
    } else {
        $_SESSION['error'] = 'Error submitting answer';
    }
    $stmt->close();
    
    header('Location: questions.php?' . http_build_query($_GET));
    exit;
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Customer Questions</h1>
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
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="answered" <?= $status === 'answered' ? 'selected' : '' ?>>Answered</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="questions.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Question</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($questions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No questions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($questions as $question): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($question['user_name'] ? $question['user_name'] : $question['name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($question['user_email'] ? $question['user_email'] : $question['email']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($question['question']) ?></div>
                                        <?php if ($question['answer']): ?>
                                            <div class="mt-2 p-2 bg-light rounded">
                                                <strong>Answer:</strong> <?= htmlspecialchars($question['answer']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($question['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $question['status'] === 'answered' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($question['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal<?= $question['id'] ?>">
                                            <i class="fas fa-reply"></i> Reply
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal -->
                                <div class="modal fade" id="questionModal<?= $question['id'] ?>" tabindex="-1" aria-labelledby="questionModalLabel<?= $question['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="questionModalLabel<?= $question['id'] ?>">
                                                    Reply to <?= htmlspecialchars($question['user_name'] ? $question['user_name'] : $question['name']) ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <p><strong>Question:</strong></p>
                                                    <div class="p-2 bg-light rounded">
                                                        <?= nl2br(htmlspecialchars($question['question'])) ?>
                                                    </div>
                                                </div>
                                                
                                                <form method="POST">
                                                    <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                                    <div class="mb-3">
                                                        <label for="answer<?= $question['id'] ?>" class="form-label">Your Answer</label>
                                                        <textarea class="form-control" id="answer<?= $question['id'] ?>" name="answer" rows="5" required><?= htmlspecialchars($question['answer']) ?></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Submit Answer</button>
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