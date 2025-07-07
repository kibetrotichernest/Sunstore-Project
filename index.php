<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Sunstore Industries - Premium Solar Solutions in Kenya";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Get featured data
$featured_products = get_featured_products();
$special_offers = get_special_offers(3);
$categories = get_categories_with_count();
$latest_projects = get_latest_projects($pdo, 4);
?>
<style>
    /* Custom Add to Cart Button */
    .btn-custom-addtocart {
        background-color: white;
        color: #997346;
        border: 1px solid #997346;
        transition: all 0.3s ease;
    }
    
    .btn-custom-addtocart:hover {
        background-color: #997346;
        color: white;
        border-color: #997346;
    }
    
    /* WhatsApp Button */
    .btn-success {
        background-color: #25D366;
        border-color: #25D366;
    }
    
    .btn-success:hover {
        background-color: #128C7E;
        border-color: #128C7E;
    }
    
    /* View Details Button */
    .btn-outline-primary {
        color: #134327;
        border-color: #134327;
    }
    
    .btn-outline-primary:hover {
        background-color: #134327;
        color: white;
    }
    
    /* Product Card Styling */
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .discount-badge {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
    }
    
    /* Image Gallery Styles */
    .product-thumbnails .img-thumbnail {
        transition: all 0.3s ease;
        border-width: 2px;
    }
    .product-thumbnails .img-thumbnail:hover {
        transform: scale(1.05);
    }
    .product-thumbnails .img-thumbnail.active-thumbnail {
        border-color: #134327 !important;
        opacity: 1 !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .thumbnail-item {
        margin: 0 5px 5px 0;
    }
</style>
<!-- Main Content -->
<div class="container my-4">
    <div class="row">
        <!-- Left Sidebar -->
        <div class="col-md-3">
            <!-- Product Categories -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Product Categories</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach($categories as $category): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="category.php?id=<?= $category['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($category['name']) ?>
                                </a>
                                <span class="badge bg-primary rounded-pill"><?= $category['product_count'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Price Filter -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Filter by Price</h5>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input price-filter" type="checkbox" id="price1" value="0-10000">
                        <label class="form-check-label" for="price1">Ksh 0 - Ksh 10,000</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input price-filter" type="checkbox" id="price2" value="10000-50000">
                        <label class="form-check-label" for="price2">Ksh 10,000 - Ksh 50,000</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input price-filter" type="checkbox" id="price3" value="50000-100000">
                        <label class="form-check-label" for="price3">Ksh 50,000 - Ksh 100,000</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input price-filter" type="checkbox" id="price4" value="100000-">
                        <label class="form-check-label" for="price4">Above Ksh 100,000</label>
                    </div>
                </div>
            </div>
            
            <!-- Brand Filter -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Choose Brand</h5>
                </div>
                <div class="card-body">
                    <select class="form-select" id="brand-filter">
                        <option selected value="all">All Brands</option>
                        <?php foreach(get_brands() as $brand): ?>
                            <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Special Offers -->
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Special Offers</h5>
                </div>
                <div class="card-body">
                    <?php foreach($special_offers as $offer): ?>
                        <div class="text-center mb-3">
                            <img src="/sunstore-industries/admin/assets/products/<?= $offer['image'] ?>" class="img-fluid mb-2" style="height: 120px; width: auto;">
                            <h6><?= htmlspecialchars($offer['name']) ?></h6>
                            <p class="text-danger fw-bold"><?= $offer['discount'] ?>% OFF</p>
                            <a href="product.php?id=<?= $offer['id'] ?>" class="btn btn-sm btn-primary w-100">Shop Now</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
<!-- Main Content Area -->
<div class="col-md-9">
    <!-- Featured Products -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Featured Solar Products</h5>
            <a href="product.php" class="text-white">View All <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach($featured_products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <?php if($product['discount'] > 0): ?>
                            <div class="position-absolute top-0 end-0 m-2 discount-badge">
                                <?= $product['discount'] ?>% OFF
                            </div>
                        <?php endif; ?>
                        
                        <img src="/sunstore-industries/admin/assets/products/<?= $product['image'] ?>" class="card-img-top p-3" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            
                            <?php if($product['discount'] > 0): ?>
                                <?php $discounted_price = $product['price'] * (1 - ($product['discount']/100)); ?>
                                <p class="card-text text-danger fw-bold">Ksh <?= number_format($discounted_price, 2) ?></p>
                                <p class="card-text text-decoration-line-through text-muted">Ksh <?= number_format($product['price'], 2) ?></p>
                            <?php else: ?>
                                <p class="card-text text-danger fw-bold">Ksh <?= number_format($product['price'], 2) ?></p>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 mt-3">
                                <!-- View Details Button -->
                                <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-info-circle"></i> View Details
                                </a>
                                
                                <!-- Add to Cart Button -->
                                <form method="post" action="product.php" class="d-grid">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-custom-addtocart">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </form>
                                
                                <!-- WhatsApp Inquiry Button -->
                                <a href="https://wa.me/+254743392675?text=<?= urlencode("I'm interested in: ".$product['name']." (Product ID: ".$product['id'].") - ".SITE_URL."/product.php?id=".$product['id']) ?>" 
                                   class="btn btn-outline-success" target="_blank">
                                   <i class="fab fa-whatsapp"></i> WhatsApp Inquiry
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

    <!-- Recent Projects Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Our Recent Solar Projects</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach($latest_projects as $project): ?>
                    <div class="col-md-6 mb-4">
                        <div class="project-card h-100 shadow-sm rounded overflow-hidden">
                            <div class="recent-project-img-container p-3">
                                <img src="/sunstore-industries/admin/assets/projects/<?= $project['image'] ?>" class="img-fluid" alt="<?= htmlspecialchars($project['title']) ?>" loading="lazy">
                                <?php if($project['video']): ?>
                                    <div class="project-video-btn position-absolute top-50 start-50 translate-middle">
                                        <a href="#" class="video-play-btn" data-bs-toggle="modal" data-bs-target="#projectVideoModal" data-video="<?= htmlspecialchars($project['video']) ?>">
                                            <i class="fas fa-play-circle"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h4><?= htmlspecialchars($project['title']) ?></h4>
                                <div class="d-flex mb-3">
                                    <span class="badge bg-primary me-2"><?= htmlspecialchars($project['location']) ?></span>
                                    <span class="badge bg-<?= $project['status'] == 'Completed' ? 'success' : ($project['status'] == 'Ongoing' ? 'warning' : 'info') ?>">
                                        <?= htmlspecialchars($project['status']) ?>
                                    </span>
                                </div>
                                <p><?= htmlspecialchars($project['description']) ?></p>
                                <ul class="project-specs list-unstyled">
                                    <?php 
                                    $specs = (array)($project['specs'] ?? []);
                                    foreach ($specs as $spec): 
                                        if (is_string($spec)) {
                                            $display_spec = $spec;
                                        } elseif (is_array($spec)) {
                                            $display_spec = implode(', ', $spec);
                                        } else {
                                            $display_spec = '';
                                        }
                                    ?>
                                        <li><i class="fas fa-check-circle text-primary me-2"></i> <?= htmlspecialchars($display_spec) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="text-center mt-3">
                                    <a href="project-details.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- View More Button -->
            <div class="text-center mt-3">
                <a href="projects.php" class="btn btn-primary btn-lg">View All Projects</a>
            </div>
        </div>
    </div>

    <!-- Blog Container -->
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Why Choose Solar Energy in Kenya?</h4>
            <div class="row">
                <!-- Blog Item 1 -->
                <div class="col-md-6 mb-4">
                    <div class="blog-post h-100 p-3 border rounded">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-3 me-3">
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Cost Saving</h5>
                                <small class="text-muted">Posted: <?= date('M j, Y') ?></small>
                            </div>
                        </div>
                        <p>Solar panels can reduce your electricity bills by up to 80% in Kenya. With rising electricity costs, solar provides a stable, predictable energy cost for your home or business.</p>
                        <!-- <a href="blogs.php" class="btn btn-sm btn-outline-primary">Read More</a> -->
                    </div>
                </div>
                
                <!-- Blog Item 2 -->
                <div class="col-md-6 mb-4">
                    <div class="blog-post h-100 p-3 border rounded">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-3 me-3">
                                <i class="fas fa-bolt fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Energy Independence</h5>
                                <small class="text-muted">Posted: <?= date('M j, Y', strtotime('-3 days')) ?></small>
                            </div>
                        </div>
                        <p>No more power outages or unreliable grid electricity. Solar power gives you complete control over your energy production, especially important for businesses in Kenya.</p>
                        <!-- <a href="blogs.php" class="btn btn-sm btn-outline-primary">Read More</a> -->
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Blog Item 3 -->
                <div class="col-md-6 mb-4">
                    <div class="blog-post h-100 p-3 border rounded">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-3 me-3">
                                <i class="fas fa-leaf fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Eco Friendly</h5>
                                <small class="text-muted">Posted: <?= date('M j, Y', strtotime('-1 week')) ?></small>
                            </div>
                        </div>
                        <p>Solar energy is clean and renewable, helping to reduce Kenya's carbon footprint. By going solar, you contribute to environmental conservation and sustainable development.</p>
                        <!-- <a href="blogs.php" class="btn btn-sm btn-outline-primary">Read More</a> -->
                    </div>
                </div>
                
                <!-- Blog Item 4 -->
                <div class="col-md-6 mb-4">
                    <div class="blog-post h-100 p-3 border rounded">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-3 me-3">
                                <i class="fas fa-award fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Premium Quality</h5>
                                <small class="text-muted">Posted: <?= date('M j, Y', strtotime('-2 weeks')) ?></small>
                            </div>
                        </div>
                        <p>We offer only the highest quality solar products with industry-leading warranties. Our systems are designed for Kenya's climate and deliver reliable performance for decades.</p>
                        <!-- <a href="blogs.php" class="btn btn-sm btn-outline-primary">Read More</a> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
            
            <div class="recent-project-img-container p-3">
    <img src="/sunstore-industries/admin/assets/projects/<?= $project['image'] ?>" class="img-fluid" alt="<?= htmlspecialchars($project['title']) ?>" loading="lazy">
</div>

<div class="p-4">
    <!-- Project title, badges, description, etc. -->
    <h4><?= htmlspecialchars($project['title']) ?></h4>
    <div class="d-flex mb-3">
        <span class="badge bg-primary me-2"><?= htmlspecialchars($project['location']) ?></span>
        <span class="badge bg-<?= $project['status'] == 'Completed' ? 'success' : ($project['status'] == 'Ongoing' ? 'warning' : 'info') ?>">
            <?= htmlspecialchars($project['status']) ?>
        </span>
    </div>
    <p><?= htmlspecialchars($project['description']) ?></p>

    <!-- Video Button (Now at the bottom) -->
    <?php if($project['video']): ?>
        <div class="text-center mt-3"> <!-- Center-align if needed -->
            <a href="#" class="btn btn-sm btn-outline-primary video-play-btn" data-bs-toggle="modal" data-bs-target="#projectVideoModal" data-video="<?= htmlspecialchars($project['video']) ?>">
                <i class="fas fa-play-circle me-1"></i> Watch Video
            </a>
        </div>
    <?php endif; ?>
</div>
            <script>
<script>
// Project video modal handler
document.addEventListener('DOMContentLoaded', function() {
    // Video modal
    var videoModal = document.getElementById('projectVideoModal');
    var videoFrame = document.getElementById('projectVideoFrame');
    
    videoModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var videoUrl = button.getAttribute('data-video');
        videoFrame.src = videoUrl;
    });
    
    videoModal.addEventListener('hide.bs.modal', function() {
        videoFrame.src = '';
    });

    // Filtering functionality
    const projectItems = document.querySelectorAll('.project-item');
    const filters = document.querySelectorAll('.project-filter li');
    
    filters.forEach(filter => {
        filter.addEventListener('click', function() {
            const filterGroup = this.parentElement.getAttribute('data-filter');
            const value = this.getAttribute('data-value');
            
            // Update active state
            this.parentElement.querySelectorAll('li').forEach(li => {
                li.classList.remove('active');
            });
            this.classList.add('active');
            
            // Filter projects
            filterProjects();
        });
    });

    // Sort functionality
    const sortOptions = document.querySelectorAll('.sort-option');
    sortOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('sortDropdown').textContent = 'Sort By: ' + this.textContent;
            sortProjects(this.getAttribute('data-sort'));
        });
    });

    // Search functionality
    const searchInput = document.getElementById('projectSearch');
    searchInput.addEventListener('input', function() {
        filterProjects();
    });

    function filterProjects() {
        const activeFilters = {
            category: document.querySelector('[data-filter="category"] .active').getAttribute('data-value'),
            location: document.querySelector('[data-filter="location"] .active').getAttribute('data-value'),
            status: document.querySelector('[data-filter="status"] .active').getAttribute('data-value')
        };
        
        const searchTerm = searchInput.value.toLowerCase();
        
        projectItems.forEach(item => {
            const matchesCategory = activeFilters.category === 'all' || 
                                   item.getAttribute('data-category').includes(activeFilters.category);
            const matchesLocation = activeFilters.location === 'all' || 
                                   item.getAttribute('data-location').includes(activeFilters.location);
            const matchesStatus = activeFilters.status === 'all' || 
                                  item.getAttribute('data-status').includes(activeFilters.status);
            const matchesSearch = item.textContent.toLowerCase().includes(searchTerm);
            
            if (matchesCategory && matchesLocation && matchesStatus && matchesSearch) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function sortProjects(criteria) {
        const container = document.getElementById('projectsContainer');
        const items = Array.from(container.querySelectorAll('.project-item'));
        
        items.sort((a, b) => {
            switch(criteria) {
                case 'recent':
                    return new Date(b.getAttribute('data-date')) - new Date(a.getAttribute('data-date'));
                case 'oldest':
                    return new Date(a.getAttribute('data-date')) - new Date(b.getAttribute('data-date'));
                case 'size':
                    return parseFloat(b.getAttribute('data-size')) - parseFloat(a.getAttribute('data-size'));
                default:
                    return 0;
            }
        });
        
        items.forEach(item => container.appendChild(item));
    }
});
</script>
</script>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>