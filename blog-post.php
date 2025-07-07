<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

// At the top of your script, before any HTML output
$post = []; // Initialize as empty array to prevent undefined variable errors

// Fetch post data from database
if (isset($_GET['slug'])) {
    $slug = $conn->real_escape_string($_GET['slug']);
    $result = $conn->query("SELECT * FROM blog_posts WHERE slug = '$slug'");
    $post = $result->fetch_assoc();
}

// Initialize post_content with fallback
$post_content = !empty($post['content']) ? format_blog_content($post['content']) : 
    '<div class="alert alert-info">This post is currently unavailable.</div>';
    
// Get the post from slug
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header("Location: blogs.php");
    exit();
}

$post = get_blog_post_by_slug($_GET['slug']);

if (!$post) {
    header("Location: blogs.php");
    exit();
}

$page_title = htmlspecialchars($post['title']) . " | Sunstore Industries Blog";
require_once 'includes/header.php';

// Get related posts
$related_posts = get_related_blog_posts($post['id'], $post['category_id']);
?>

<!-- Main Content -->
<div class="container my-5">
    <div class="row">
        <!-- Left Sidebar (same as index.php) -->
        <div class="col-md-3">
            <?php require_once 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-md-9">
            <!-- Blog Post Content -->
            <article class="card mb-4">
                <!-- Featured Image -->
                <div class="blog-post-featured-image">
                    <img src="/sunstore-industries/admin/assets/blog/<?= $post['featured_image'] ?>" 
                         alt="<?= htmlspecialchars($post['title']) ?>" 
                         class="img-fluid w-100 rounded-top">
                </div>
                
                <div class="card-body">
                    <!-- Post Meta -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="badge bg-primary me-2">
                                <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?>
                            </span>
                            <span class="text-muted small">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?= !empty($post['published_at']) ? date('F j, Y', strtotime($post['published_at'])) : 'Not published yet' ?>
                            </span>
                        </div>
                        <div class="text-muted small">
                            <i class="far fa-eye me-1"></i> <?= number_format($post['views']) ?> views
                        </div>
                    </div>
                    
                    <!-- Post Title -->
                    <h1 class="mb-3"><?= htmlspecialchars($post['title']) ?></h1>
                    
                    <!-- Author Info -->
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0">
                            <img src="/sunstore-industries/admin/assets/users/default-avatar.jpg" 
                                 class="rounded-circle" 
                                 width="50" 
                                 alt="Author">
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= htmlspecialchars($post['author_name'] ?? 'Sunstore Team') ?></h6>
                            <small class="text-muted">Solar Energy Expert</small>
                        </div>
                    </div>
                    
                    <div class="blog-post-content mb-5" id="post-content">
    <?= $post_content ?>
    <div class="content-loading-spinner d-none">
        <div class="spinner-border text-primary"></div>
    </div>
</div>

<script>
// Lazy load embedded content
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Load any embedded content
                entry.target.querySelectorAll('iframe, video').forEach(el => {
                    el.src = el.dataset.src;
                });
                observer.unobserve(entry.target);
            }
        });
    }, {rootMargin: '200px'});
    
    observer.observe(document.getElementById('post-content'));
});
</script>
                    <!-- Tags -->
                    <?php if(!empty($post['tags'])): ?>
                        <div class="mb-5">
                            <h5 class="mb-3">Tags</h5>
                            <div class="tag-cloud">
                                <?php foreach($post['tags'] as $tag): ?>
                                    <a href="blogs.php?search=<?= urlencode($tag['name']) ?>" class="btn btn-sm btn-outline-secondary me-2 mb-2">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                   <!-- Share Buttons -->
<div class="mb-5">
    <h5 class="mb-3">Share This Post</h5>
    <div class="share-buttons">
        <?php
        // Safely get current URL
        $current_url = isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']) 
            ? "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" 
            : '';
        $encoded_url = urlencode($current_url);
        
        // Safely get post data with fallbacks
        $post_title = !empty($post['title']) ? $post['title'] : 'Check this out';
        $post_excerpt = !empty($post['excerpt']) ? $post['excerpt'] : '';
        ?>
        
        <!-- Facebook -->
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encoded_url ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="btn btn-sm btn-outline-primary me-2">
            <i class="fab fa-facebook-f me-1"></i> Facebook
        </a>
        
        <!-- Twitter -->
        <a href="https://twitter.com/intent/tweet?url=<?= $encoded_url ?>&text=<?= urlencode($post_title) ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="btn btn-sm btn-outline-info me-2">
            <i class="fab fa-twitter me-1"></i> Twitter
        </a>
        
        <!-- LinkedIn -->
        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= $encoded_url ?>&title=<?= urlencode($post_title) ?>&summary=<?= urlencode($post_excerpt) ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="btn btn-sm btn-outline-primary">
            <i class="fab fa-linkedin-in me-1"></i> LinkedIn
        </a>
    </div>
</div>
                    
                    <!-- Related Posts -->
                    <?php if(!empty($related_posts)): ?>
                        <div class="related-posts">
                            <h3 class="mb-4">Related Articles</h3>
                            <div class="row">
                                <?php foreach($related_posts as $related): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <img src="/sunstore-industries/admin/assets/blog/<?= $related['featured_image'] ?>" 
                                                 class="card-img-top" 
                                                 alt="<?= htmlspecialchars($related['title']) ?>">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="blog-post.php?slug=<?= $related['slug'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($related['title']) ?>
                                                    </a>
                                                </h5>
                                                <p class="card-text small text-muted">
                                                    <?= date('M j, Y', strtotime($related['published_at'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
            
            <!-- Comments Section (optional) -->
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="mb-4">Leave a Comment</h3>
                    <form>
                        <div class="mb-3">
                            <label for="commentName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="commentName" required>
                        </div>
                        <div class="mb-3">
                            <label for="commentEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="commentEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="commentText" class="form-label">Comment</label>
                            <textarea class="form-control" id="commentText" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Comment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>