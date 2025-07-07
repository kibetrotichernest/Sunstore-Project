<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

$projects = $conn->query("SELECT * FROM projects ORDER BY date_completed DESC");

$page_title = "Manage Projects";
include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Projects</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Project
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Size (kW)</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($project = $projects->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($project['title']) ?></td>
                                    <td><?= htmlspecialchars($project['location']) ?></td>
                                    <td><?= htmlspecialchars($project['category']) ?></td>
                                    <td><?= $project['size'] ?? 'N/A' ?></td>
                                    <td><?= date('M Y', strtotime($project['date_completed'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $project['status'] == 'Completed' ? 'success' : 
                                            ($project['status'] == 'Ongoing' ? 'warning' : 'info') 
                                        ?>">
                                            <?= $project['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>