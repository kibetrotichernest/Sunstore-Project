<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $published_at = $is_published ? (isset($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d H:i:s')) : null;
    
    // Get tags
    $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    // Handle image upload
    $featured_image = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../admin/assets/blog/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array(strtolower($file_ext), $allowed_ext)) {
            $filename = 'blog_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $destination)) {
                $featured_image = $filename;
            } else {
                $_SESSION['error'] = 'Failed to upload image';
            }
        } else {
            $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
        }
    }
    
    // Validate inputs
    if (empty($title)) {
        $_SESSION['error'] = 'Title is required';
    } elseif (empty($slug)) {
        $_SESSION['error'] = 'Slug is required';
    } elseif (empty($content)) {
        $_SESSION['error'] = 'Content is required';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert blog post
            $stmt = $pdo->prepare("INSERT INTO blog_posts 
                (title, slug, content, excerpt, featured_image, category_id, published_at, is_featured, meta_title, meta_description, author_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $title, 
                $slug, 
                $content, 
                $excerpt, 
                $featured_image, 
                $category_id, 
                $published_at, 
                $is_featured, 
                $meta_title, 
                $meta_description,
                $_SESSION['admin_id']
            ]);
            
            $post_id = $pdo->lastInsertId();
            
            // Insert tags
            if (!empty($tags)) {
                $tag_stmt = $pdo->prepare("INSERT INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tag_id) {
                    $tag_stmt->execute([$post_id, $tag_id]);
                }
            }
            
            $pdo->commit();
            
            $_SESSION['message'] = 'Blog post added successfully';
            header('Location: manage.php');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error adding blog post: ' . $e->getMessage();
        }
    }
}

// Get categories and tags for form
$categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$tags = $pdo->query("SELECT * FROM blog_tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Blog Post</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="manage.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Posts
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                Post Content
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug *</label>
                                    <input type="text" class="form-control" id="slug" name="slug" required>
                                    <small class="text-muted">URL-friendly version of the title (lowercase, hyphens instead of spaces)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">Content *</label>
                                    <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="excerpt" class="form-label">Excerpt</label>
                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3"></textarea>
                                    <small class="text-muted">A short summary of your post (optional)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                SEO Settings
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="meta_title" class="form-label">Meta Title</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title">
                                    <small class="text-muted">Title for search engines (defaults to post title if empty)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3"></textarea>
                                    <small class="text-muted">Description for search engines (defaults to excerpt if empty)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                Publish Settings
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="published_at" class="form-label">Publish Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="published_at" name="published_at" 
                                           value="<?= date('Y-m-d\TH:i') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" checked>
                                        <label class="form-check-label" for="is_published">Publish immediately</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                        <label class="form-check-label" for="is_featured">Featured Post</label>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Save Post</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                Categories
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Select Category</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Uncategorized</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                Tags
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Select Tags</label>
                                    <select class="form-select" id="tags" name="tags[]" multiple>
                                        <?php foreach ($tags as $tag): ?>
                                            <option value="<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple tags</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                Featured Image
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="featured_image" class="form-label">Upload Image</label>
                                    <input class="form-control" type="file" id="featured_image" name="featured_image" accept="image/*">
                                    <small class="text-muted">Recommended size: 1200x630 pixels</small>
                                </div>
                                
                                <div id="image-preview" class="mt-2" style="display: none;">
                                    <img id="preview-image" src="#" alt="Preview" class="img-fluid rounded">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- Include CKEditor for rich text editing -->
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
<script>
// Initialize CKEditor
CKEDITOR.replace('content', {
    toolbar: [
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
        { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'insert', items: ['Image', 'Table', 'HorizontalRule'] },
        { name: 'styles', items: ['Styles', 'Format'] },
        { name: 'document', items: ['Source'] }
    ],
    height: 400
});

// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const slug = this.value.toLowerCase()
        .replace(/\s+/g, '-')           // Replace spaces with -
        .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
        .replace(/\-\-+/g, '-')         // Replace multiple - with single -
        .replace(/^-+/, '')             // Trim - from start of text
        .replace(/-+$/, '');            // Trim - from end of text
    document.getElementById('slug').value = slug;
    
    // Also set meta title if empty
    if (!document.getElementById('meta_title').value) {
        document.getElementById('meta_title').value = this.value;
    }
});

// Set meta description from excerpt if empty
document.getElementById('excerpt').addEventListener('input', function() {
    if (!document.getElementById('meta_description').value) {
        document.getElementById('meta_description').value = this.value;
    }
});

// Image preview
document.getElementById('featured_image').addEventListener('change', function(e) {
    const preview = document.getElementById('preview-image');
    const previewContainer = document.getElementById('image-preview');
    
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(this.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
});

// Initialize select2 for tags (if you include select2 library)
// $(document).ready(function() {
//     $('#tags').select2({
//         placeholder: "Select tags",
//         allowClear: true
//     });
// });
</script>

<?php include '../includes/admin-footer.php'; ?>