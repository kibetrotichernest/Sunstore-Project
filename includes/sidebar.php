<?php
/**
 * Blog Sidebar Component
 * Includes categories, popular posts, tags, and newsletter signup
 */

?>
<div class="col-md-9">
    <!-- Categories Widget -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Blog Categories</h5>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="blogs.php" class="text-decoration-none <?= !isset($_GET['category']) ? 'fw-bold' : '' ?>">
                        All Categories
                    </a>
                    <span class="badge bg-primary rounded-pill"><?= count(get_all_blog_posts()) ?></span>
                </li>
                <?php foreach(get_blog_categories() as $category): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="blogs.php?category=<?= $category['slug'] ?>"
                           class="text-decoration-none <?= (isset($_GET['category']) && $_GET['category'] == $category['slug']) ? 'fw-bold' : '' ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </a>
                        <span class="badge bg-primary rounded-pill"><?= get_category_post_count($category['id']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <!-- Popular Posts Widget -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Popular Articles</h5>
        </div>
        <div class="card-body">
            <?php foreach(get_popular_blog_posts(3) as $post): ?>
                <div class="mb-3 d-flex">
                    <div class="flex-shrink-0">
                        <img src="/Sunstore-Project/admin/assets/blog/<?= $post['featured_image'] ?>"
                             alt="<?= htmlspecialchars($post['title']) ?>"
                             class="rounded" width="60" height="60" style="object-fit: cover;">
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mt-0 mb-1">
                            <a href="blog-post.php?slug=<?= $post['slug'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h6>
                        <small class="text-muted">
                            <i class="far fa-eye"></i> <?= number_format($post['views']) ?> views
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tags Widget -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Popular Tags</h5>
        </div>
        <div class="card-body">
            <div class="tag-cloud">
                <?php foreach(get_popular_tags(15) as $tag): ?>
                    <a href="blogs.php?search=<?= urlencode($tag['name']) ?>"
                       class="btn btn-sm btn-outline-secondary me-2 mb-2">
                        <?= htmlspecialchars($tag['name']) ?>
                        <span class="badge bg-primary ms-1"><?= $tag['count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Newsletter Widget -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Solar Insights Newsletter</h5>
        </div>
        <div class="card-body">
            <p class="small">Get the latest solar energy news, tips, and special offers delivered to your inbox.</p>
            <form id="newsletterForm">
                <div class="mb-3">
                    <input type="email" class="form-control form-control-sm" placeholder="Your email address" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Subscribe</button>
            </form>
        </div>
    </div>

    <!-- Call-to-Action Widget -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Need Solar Solutions?</h5>
        </div>
        <div class="card-body text-center">
            <img src="/Sunstore-Project/assets/images/solar-expert.jpg" class="img-fluid rounded-circle mb-3" width="100" alt="Solar Expert">
            <h5>Talk to Our Experts</h5>
            <p class="small">Get free consultation for your solar needs</p>
            <a href="contact.php" class="btn btn-danger w-100">Contact Us</a>
        </div>
    </div>
</div>
