<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Get project ID from URL
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch project data
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header("Location: view.php?error=notfound");
    exit();
}

// Get categories and locations for dropdowns
$categories = $conn->query("SELECT DISTINCT category FROM projects");
$locations = $conn->query("SELECT DISTINCT location FROM projects");

$page_title = "Edit Project";
include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Project</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Projects
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="update.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $project['id'] ?>">
                        <input type="hidden" name="current_image" value="<?= $project['image'] ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Project Title*</label>
                                <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($project['title']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">System Size (kW)*</label>
                                <input type="number" step="0.01" class="form-control" name="size" value="<?= $project['size'] ?? 0 ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Category*</label>
                                <select class="form-select" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php while($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($cat['category']) ?>" <?= ($cat['category'] == $project['category']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Location*</label>
                                <select class="form-select" name="location" required>
                                    <option value="">Select Location</option>
                                    <?php while($loc = $locations->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($loc['location']) ?>" <?= ($loc['location'] == $project['location']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loc['location']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status*</label>
                                <select class="form-select" name="status" required>
                                    <option value="Completed" <?= ($project['status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
                                    <option value="Ongoing" <?= ($project['status'] == 'Ongoing') ? 'selected' : '' ?>>Ongoing</option>
                                    <option value="Upcoming" <?= ($project['status'] == 'Upcoming') ? 'selected' : '' ?>>Upcoming</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description*</label>
                            <textarea class="form-control" name="description" rows="3" required><?= htmlspecialchars($project['description']) ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Completion Date*</label>
                                <input type="date" class="form-control" name="date_completed" value="<?= $project['date_completed'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Featured Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <?php if ($project['image']): ?>
                                    <div class="mt-2">
                                        <small>Current Image:</small>
                                        <img src="../../assets/projects/<?= $project['image'] ?>" style="max-height: 100px;" class="img-thumbnail mt-1">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Project Specifications (One per line)</label>
                            <textarea class="form-control" name="specs" rows="5"><?= 
                                isset($project['specs']) ? 
                                implode("\n", json_decode($project['specs'], true)) : 
                                '' 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Video URL (Optional)</label>
                            <input type="url" class="form-control" name="video" value="<?= $project['video'] ?? '' ?>" placeholder="https://youtube.com/embed/...">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Project</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>