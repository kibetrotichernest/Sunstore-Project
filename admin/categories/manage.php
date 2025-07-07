<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Handle form submission for adding/editing categories
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate inputs
    if (empty($name)) {
        $_SESSION['error'] = 'Category name is required';
    } else {
        if ($id > 0) {
            // Update existing category
            $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssii", $name, $slug, $description, $parent_id, $is_active, $id);
        } else {
            // Insert new category
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, parent_id, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $slug, $description, $parent_id, $is_active);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = $id > 0 ? 'Category updated successfully' : 'Category added successfully';
            header('Location: manage.php');
            exit;
        } else {
            $_SESSION['error'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if category has children
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $has_children = $stmt->get_result()->fetch_row()[0] > 0;
    $stmt->close();
    
    if ($has_children) {
        $_SESSION['error'] = 'Cannot delete category with subcategories. Please reassign or delete subcategories first.';
    } else {
        // Delete category
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Category deleted successfully';
        } else {
            $_SESSION['error'] = 'Error deleting category';
        }
        $stmt->close();
    }
    
    header('Location: manage.php');
    exit;
}

// Get all categories for dropdown and listing
$categories = $conn->query("
    SELECT c.*, p.name as parent_name 
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.name
")->fetch_all(MYSQLI_ASSOC);

// Prepare categories tree for display
function buildCategoryTree($categories, $parent_id = NULL, $depth = 0) {
    $tree = [];
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth * 2);
    
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parent_id) {
            $category['indent'] = $indent;
            $tree[] = $category;
            $tree = array_merge($tree, buildCategoryTree($categories, $category['id'], $depth + 1));
        }
    }
    
    return $tree;
}

$categories_tree = buildCategoryTree($categories);

// Get category to edit if edit_id is set
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($categories as $category) {
        if ($category['id'] == $edit_id) {
            $edit_category = $category;
            break;
        }
    }
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Categories</h1>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <?= $edit_category ? 'Edit Category' : 'Add New Category' ?>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php if ($edit_category): ?>
                                    <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= $edit_category ? htmlspecialchars($edit_category['name']) : '' ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug *</label>
                                    <input type="text" class="form-control" id="slug" name="slug" 
                                           value="<?= $edit_category ? htmlspecialchars($edit_category['slug']) : '' ?>" required>
                                    <small class="text-muted">URL-friendly version of the name (lowercase, hyphens instead of spaces)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="parent_id" class="form-label">Parent Category</label>
                                    <select class="form-select" id="parent_id" name="parent_id">
                                        <option value="">-- No Parent --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <?php if (!$edit_category || $category['id'] != $edit_category['id']): ?>
                                                <option value="<?= $category['id'] ?>" 
                                                    <?= $edit_category && $edit_category['parent_id'] == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?= $edit_category ? htmlspecialchars($edit_category['description']) : '' ?></textarea>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                           <?= $edit_category && $edit_category['is_active'] ? 'checked' : (!isset($edit_category) ? 'checked' : '') ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary"><?= $edit_category ? 'Update' : 'Add' ?> Category</button>
                                
                                <?php if ($edit_category): ?>
                                    <a href="manage.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Categories List
                        </div>
                        <div class="card-body">
                            <?php if (empty($categories_tree)): ?>
                                <p>No categories found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories_tree as $category): ?>
                                                <tr>
                                                    <td>
                                                        <?= $category['indent'] ?>
                                                        <?= htmlspecialchars($category['name']) ?>
                                                        <?php if ($category['parent_name']): ?>
                                                            <small class="text-muted">(Parent: <?= htmlspecialchars($category['parent_name']) ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                                            <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="manage.php?edit=<?= $category['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="manage.php?delete=<?= $category['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    if (!document.getElementById('slug').value || document.getElementById('slug').value === '<?= $edit_category ? $edit_category['slug'] : '' ?>') {
        const slug = this.value.toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
        document.getElementById('slug').value = slug;
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>