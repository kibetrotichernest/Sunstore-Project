<?php
$page_title = "Add New Product";
$page_actions = [
    ['url' => 'view.php', 'text' => 'View Products', 'type' => 'primary', 'icon' => 'list']
];
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and process form data
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $price = floatval($_POST['price']);
        $discount = floatval($_POST['discount']);
        $category_ids = $_POST['categories'] ?? [];
        $specifications = json_encode($_POST['specs'] ?? []);
        $features = json_encode($_POST['features'] ?? []);
        $status = isset($_POST['status']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // File upload configuration
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Handle file uploads
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $image = upload_file($_FILES['image'], '../assets/products/', $allowed_types, $max_size);
        } else {
            throw new Exception("Main product image is required");
        }
        
        $gallery_images = '';
        if (!empty($_FILES['gallery']['name'][0])) {
            $gallery_images = upload_multiple_files($_FILES['gallery'], '../assets/products/gallery/', $allowed_types, $max_size);
        }
        
        // Insert product
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount, image, gallery, specifications, features, status, featured) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddssssii", $name, $description, $price, $discount, $image, $gallery_images, $specifications, $features, $status, $featured);
        
        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;
            
            // Assign categories
            if (!empty($category_ids)) {
                foreach ($category_ids as $category_id) {
                    $conn->query("INSERT INTO product_categories (product_id, category_id) VALUES ($product_id, $category_id)");
                }
            }
            
            $_SESSION['success'] = "Product added successfully!";
            header("Location: view.php");
            exit();
        } else {
            throw new Exception("Error adding product: " . $conn->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
require_once '../includes/admin-header.php';
// Get categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name");
?>


<div class="container-fluid">
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Product Information</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (<?= CURRENCY ?>) *</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discount" class="form-label">Discount (%)</label>
                                    <input type="number" step="0.1" min="0" max="100" class="form-control" id="discount" name="discount" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Categories *</label>
                            <div class="row">
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $category['id'] ?>" id="cat-<?= $category['id'] ?>">
                                        <label class="form-check-label" for="cat-<?= $category['id'] ?>">
                                            <?= $category['name'] ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="specs" class="form-label">Specifications</label>
                            <div id="specifications-container">
                                <div class="row mb-2 specification-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="specs[key][]" placeholder="Specification name">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="specs[value][]" placeholder="Specification value">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-spec"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add-spec" class="btn btn-sm btn-secondary mt-2">
                                <i class="fas fa-plus me-1"></i> Add Specification
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label for="features" class="form-label">Features</label>
                            <div id="features-container">
                                <div class="input-group mb-2 feature-row">
                                    <input type="text" class="form-control" name="features[]" placeholder="Feature description">
                                    <button class="btn btn-outline-danger remove-feature" type="button"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" id="add-feature" class="btn btn-sm btn-secondary mt-2">
                                <i class="fas fa-plus me-1"></i> Add Feature
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                    <label class="form-check-label" for="status">Active Product</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="featured" name="featured">
                                    <label class="form-check-label" for="featured">Featured Product</label>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Product Images</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="image" class="form-label">Main Image *</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                        <div class="mt-2">
                            <img id="image-preview" src="../assets/products/placeholder.png" class="img-thumbnail" style="max-height: 200px; display: none;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="gallery" class="form-label">Gallery Images</label>
                        <input type="file" class="form-control" id="gallery" name="gallery[]" multiple accept="image/*">
                        <div id="gallery-preview" class="d-flex flex-wrap mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-save me-2"></i> Save Product
                    </button>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>

<script>
// Image preview for main image
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

// Gallery images preview
document.getElementById('gallery').addEventListener('change', function(e) {
    const files = e.target.files;
    const preview = document.getElementById('gallery-preview');
    preview.innerHTML = '';
    
    for (let i = 0; i < files.length; i++) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'position-relative me-2 mb-2';
            div.style.width = '100px';
            div.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 remove-gallery-image">
                    <i class="fas fa-times"></i>
                </button>
            `;
            preview.appendChild(div);
        }
        reader.readAsDataURL(files[i]);
    }
});

// Add specification row
document.getElementById('add-spec').addEventListener('click', function() {
    const container = document.getElementById('specifications-container');
    const newRow = document.createElement('div');
    newRow.className = 'row mb-2 specification-row';
    newRow.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" name="specs[key][]" placeholder="Specification name">
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control" name="specs[value][]" placeholder="Specification value">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-sm remove-spec"><i class="fas fa-times"></i></button>
        </div>
    `;
    container.appendChild(newRow);
});

// Add feature row
document.getElementById('add-feature').addEventListener('click', function() {
    const container = document.getElementById('features-container');
    const newRow = document.createElement('div');
    newRow.className = 'input-group mb-2 feature-row';
    newRow.innerHTML = `
        <input type="text" class="form-control" name="features[]" placeholder="Feature description">
        <button class="btn btn-outline-danger remove-feature" type="button"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(newRow);
});

// Remove elements when buttons are clicked
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-spec') || e.target.closest('.remove-spec')) {
        e.target.closest('.specification-row').remove();
    }
    
    if (e.target.classList.contains('remove-feature') || e.target.closest('.remove-feature')) {
        e.target.closest('.feature-row').remove();
    }
    
    if (e.target.classList.contains('remove-gallery-image') || e.target.closest('.remove-gallery-image')) {
        e.target.closest('div').remove();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>