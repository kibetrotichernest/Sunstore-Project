<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Get all products and categories
$products = $conn->query("SELECT id, name FROM products ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
    
    // Validate
    if ($product_id <= 0) {
        $_SESSION['error'] = 'Please select a product';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // First, remove all existing category assignments for this product
            $stmt = $conn->prepare("DELETE FROM product_categories WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->close();
            
            // Then add the new assignments
            if (!empty($category_ids)) {
                $stmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                
                foreach ($category_ids as $category_id) {
                    $category_id = intval($category_id);
                    if ($category_id > 0) {
                        $stmt->bind_param("ii", $product_id, $category_id);
                        $stmt->execute();
                    }
                }
                
                $stmt->close();
            }
            
            $conn->commit();
            $_SESSION['message'] = 'Categories assigned successfully';
            
            // Redirect to avoid form resubmission
            header('Location: assign.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = 'Error assigning categories: ' . $e->getMessage();
        }
    }
}

// Get current assignments when a product is selected
$current_categories = [];
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $result = $conn->query("SELECT category_id FROM product_categories WHERE product_id = $product_id");
    while ($row = $result->fetch_assoc()) {
        $current_categories[] = $row['category_id'];
    }
}

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Assign Products to Categories</h1>
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
                    Assign Categories
                </div>
                <div class="card-body">
                    <form method="POST" id="assignForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="product_id" class="form-label">Select Product *</label>
                                <select class="form-select" id="product_id" name="product_id" required>
                                    <option value="">-- Select Product --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['id'] ?>" 
                                            <?= (isset($_POST['product_id']) && $_POST['product_id'] == $product['id']) || (isset($_GET['product_id']) && $_GET['product_id'] == $product['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($product['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Select Categories</label>
                                <div class="row">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="category_<?= $category['id'] ?>" 
                                                       name="category_ids[]" 
                                                       value="<?= $category['id'] ?>"
                                                       <?= in_array($category['id'], $current_categories) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="category_<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Assignments</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// When product selection changes, reload the page with the product_id parameter
document.getElementById('product_id').addEventListener('change', function() {
    if (this.value) {
        window.location.href = 'assign.php?product_id=' + this.value;
    } else {
        window.location.href = 'assign.php';
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>