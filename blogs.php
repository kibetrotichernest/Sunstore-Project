<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Solar Energy Blogs & Articles - Sunstore Industries";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Pagination setup
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 6;
$offset = ($current_page - 1) * $per_page;

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// Get blog posts
$blog_posts = get_blog_posts($per_page, $offset, $category, $search);
$total_posts = count(get_blog_posts(0, 0, $category, $search)); // Get total count without limit
$total_pages = ceil($total_posts / $per_page);

// Get categories for sidebar
$categories = get_blog_categories();
$featured_posts = get_featured_blog_posts();
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
            <!-- Blog Header with Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="mb-0">Solar Energy Insights</h1>
                        <form class="d-flex" action="blogs.php" method="get">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search blogs..." value="<?= htmlspecialchars($search ?? '') ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Category Navigation -->
                    <div class="mb-4">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?= !$category ? 'active' : '' ?>" href="blogs.php">All</a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $category == $cat['slug'] ? 'active' : '' ?>"
                                        href="blogs.php?category=<?= $cat['slug'] ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Featured Posts (only show on first page when not searching/filtering) -->
            <?php if ($current_page == 1 && !$category && !$search): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Featured Articles</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($featured_posts as $post): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="featured-post h-100">
                                        <div class="featured-post-image mb-3">
                                            <a href="blog-post.php?slug=<?= $post['slug'] ?>">
                                                <img src="/Sunstore-Project/admin/assets/blog/<?= $post['featured_image'] ?>"
                                                    alt="<?= htmlspecialchars($post['title']) ?>"
                                                    class="img-fluid rounded">
                                            </a>
                                        </div>
                                        <div class="featured-post-content">
                                            <span class="badge bg-secondary mb-2">
                                                <?= date('M j, Y', strtotime($post['published_at'])) ?>
                                            </span>
                                            <h4>
                                                <a href="blog-post.php?slug=<?= $post['slug'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($post['title']) ?>
                                                </a>
                                            </h4>
                                            <p><?= htmlspecialchars($post['excerpt']) ?></p>
                                            <a href="blog-post.php?slug=<?= $post['slug'] ?>" class="btn btn-sm btn-outline-primary">
                                                Read More <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Blog Posts Listing -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (!empty($blog_posts)): ?>
                        <div class="row">
                            <?php foreach ($blog_posts as $post): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="blog-post-card h-100 shadow-sm">
                                        <div class="blog-post-image">
                                            <a href="blog-post.php?slug=<?= $post['slug'] ?>">
                                                <img src="/Sunstore-Project/admin/assets/blog/<?= $post['featured_image'] ?>"
                                                    alt="<?= htmlspecialchars($post['title']) ?>"
                                                    class="img-fluid w-100" style="height: 200px; object-fit: cover;">
                                            </a>
                                        </div>
                                        <div class="p-4">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted small">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    <?= date('M j, Y', strtotime($post['published_at'])) ?>
                                                </span>
                                                <span class="badge bg-primary">
                                                    <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?>
                                                </span>
                                            </div>
                                            <h3 class="h4">
                                                <a href="blog-post.php?slug=<?= $post['slug'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($post['title']) ?>
                                                </a>
                                            </h3>
                                            <p class="mb-3"><?= htmlspecialchars($post['excerpt']) ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="blog-post.php?slug=<?= $post['slug'] ?>" class="btn btn-sm btn-outline-primary">
                                                    Read More <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                                <span class="text-muted small">
                                                    <i class="far fa-eye me-1"></i> <?= number_format($post['views']) ?> views
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Blog pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="blogs.php?page=<?= $current_page - 1 ?><?= $category ? '&category=' . $category : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="blogs.php?page=<?= $i ?><?= $category ? '&category=' . $category : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="blogs.php?page=<?= $current_page + 1 ?><?= $category ? '&category=' . $category : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="far fa-newspaper fa-4x text-muted mb-3"></i>
                            <h3>No blog posts found</h3>
                            <p class="text-muted">We couldn't find any posts matching your criteria.</p>
                            <a href="blogs.php" class="btn btn-primary">View All Posts</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Newsletter Subscription -->
            <div class="card">
                <div class="card-body bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-0">Stay Updated on Solar Trends</h4>
                            <p class="mb-md-0">Subscribe to our newsletter for the latest solar insights</p>
                        </div>
                        <div class="col-md-6">
                            <form class="row g-2">
                                <div class="col-8">
                                    <input type="email" class="form-control" placeholder="Your email address">
                                </div>
                                <div class="col-4">
                                    <button type="submit" class="btn btn-primary w-100">Subscribe</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
