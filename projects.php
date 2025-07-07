<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Our Solar Projects - Sunstore Industries";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Get all projects with error handling
try {
    $projects = get_all_projects();
    $project_categories = get_project_categories();
    $project_locations = get_project_locations();
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Error loading projects: " . $e->getMessage());
    $error_message = "We're having trouble loading our projects. Please try again later.";
}
?>

<!-- Add this at the top of your main content -->
<div class="container my-4">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="row">
        <!-- Left Sidebar - Filters -->
        <div class="col-md-3">
            <!-- Project Categories -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Project Types</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush project-filter" data-filter="category">
                        <li class="list-group-item active" data-value="all">All Projects</li>
                        <?php foreach($project_categories as $category): ?>
                            <li class="list-group-item" data-value="<?= htmlspecialchars(strtolower($category)) ?>">
                                <?= htmlspecialchars($category) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Project Locations -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Locations</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush project-filter" data-filter="location">
                        <li class="list-group-item active" data-value="all">All Locations</li>
                        <?php foreach($project_locations as $location): ?>
                            <li class="list-group-item" data-value="<?= htmlspecialchars(strtolower($location)) ?>">
                                <?= htmlspecialchars($location) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Project Status -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Project Status</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush project-filter" data-filter="status">
                        <li class="list-group-item active" data-value="all">All Statuses</li>
                        <li class="list-group-item" data-value="completed">Completed</li>
                        <li class="list-group-item" data-value="ongoing">Ongoing</li>
                        <li class="list-group-item" data-value="upcoming">Upcoming</li>
                    </ul>
                </div>
            </div>
            
            <!-- Project Highlights -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Project Highlights</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                            <i class="fas fa-solar-panel"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">250+</h6>
                            <small class="text-muted">Projects Completed</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">1.2MW</h6>
                            <small class="text-muted">Total Installed Capacity</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">15+</h6>
                            <small class="text-muted">Counties Covered</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Projects Content -->
        <div class="col-md-9">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Our Solar Projects</h1>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Sort By: Recent
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item sort-option" href="#" data-sort="recent">Most Recent</a></li>
                        <li><a class="dropdown-item sort-option" href="#" data-sort="oldest">Oldest First</a></li>
                        <li><a class="dropdown-item sort-option" href="#" data-sort="size">System Size</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Project Search -->
            <div class="input-group mb-4">
                <input type="text" class="form-control" id="projectSearch" placeholder="Search projects...">
                <button class="btn btn-primary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <!-- Projects Grid -->
            <div class="row" id="projectsContainer">
                <?php foreach($projects as $project): ?>
                    <div class="col-md-6 mb-4 project-item" 
                         data-category="<?= htmlspecialchars(strtolower($project['category'])) ?>"
                         data-location="<?= htmlspecialchars(strtolower($project['location'])) ?>"
                         data-status="<?= htmlspecialchars(strtolower($project['status'])) ?>"
                         data-date="<?= $project['date'] ?>"
                         data-size="<?= $project['size'] ?>">
                        <div class="project-card h-100 shadow-sm rounded overflow-hidden position-relative">
                            <div class="project-image-container">
                                <img src="/sunstore-industries/admin/assets/projects/<?= $project['image'] ?>" 
                                     class="img-fluid" 
                                     alt="<?= htmlspecialchars($project['title']) ?>"
                                     loading="lazy">
                            </div>
                            
                            <div class="p-4">
                                <h4><?= htmlspecialchars($project['title']) ?></h4>
                                <div class="d-flex mb-3">
                                    <span class="badge bg-primary me-2"><?= htmlspecialchars($project['location']) ?></span>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($project['category']) ?></span>
                                </div>
                                <p><?= htmlspecialchars($project['description']) ?></p>
                                
                                <div class="project-meta mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">System Size</small>
                                            <h6 class="mb-0"><?= htmlspecialchars($project['size']) ?> kW</h6>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Completed</small>
                                            <h6 class="mb-0"><?= date('M Y', strtotime($project['date'])) ?></h6>
                                        </div>
                                    </div>
                                </div>
                                
                                <ul class="project-specs list-unstyled mb-3">
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
                                
                                <?php if($project['video']): ?>
                                    <div class="text-end">
                                        <a href="#" class="btn btn-sm video-play-btn" data-bs-toggle="modal" data-bs-target="#projectVideoModal" data-video="<?= htmlspecialchars($project['video']) ?>">
                                            <i class="fas fa-play-circle me-1" style="color: #997346;"></i> Watch Video
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="project-details.php?id=<?= $project['id'] ?>" class="btn btn-outline-primary w-100 mt-2">View Project Details</a>
                            </div>
                            
                            <div class="project-badge position-absolute top-0 end-0 m-2">
                                <span class="badge bg-<?= $project['status'] == 'Completed' ? 'success' : ($project['status'] == 'Ongoing' ? 'warning' : 'info') ?>">
                                    <?= htmlspecialchars($project['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Project pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div class="modal fade" id="projectVideoModal" tabindex="-1" aria-labelledby="projectVideoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectVideoModalLabel">Project Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9">
                    <iframe id="projectVideoFrame" src="" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Project Gallery Modal -->
<div class="modal fade" id="projectGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="galleryModalLabel">Project Gallery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="projectGalleryCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#projectGalleryCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#projectGalleryCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>