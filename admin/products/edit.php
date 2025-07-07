<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/admin-header.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: view.php');
    exit;
}

$product_id = intval($_GET['id']);

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: view.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $sku = trim($_POST['sku']);
    $quantity = intval($_POST['quantity']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Handle image upload
    $image = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            // Delete old image if it exists
            if ($image && file_exists('../../' . $image)) {
                unlink('../../' . $image);
            }
            $image = 'uploads/products/' . $filename;
        }
    }

    // Update product in database
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, sale_price = ?, sku = ?, quantity = ?, image = ?, is_featured = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssddsssiii", $name, $description, $price, $sale_price, $sku, $quantity, $image, $is_featured, $is_active, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Product updated successfully';
        header('Location: view.php');
        exit;
    } else {
        $error = "Error updating product: " . $conn->error;
    }
    $stmt->close();
}

include 'includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Product</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                Product Details
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($product['description']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                Product Data
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price *</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= $product['price'] ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sale_price" class="form-label">Sale Price</label>
                                    <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" value="<?= $product['sale_price'] ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku" value="<?= htmlspecialchars($product['sku']) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="<?= $product['quantity'] ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_featured">Featured Product</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                Product Image
                            </div>
                            <div class="card-body">
                                <?php if ($product['image']): ?>
                                    <img src="../../<?= htmlspecialchars($product['image']) ?>" class="img-thumbnail mb-3" style="max-height: 200px;">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Upload New Image</label>
                                    <input class="form-control" type="file" id="image" name="image">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>