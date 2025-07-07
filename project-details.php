<?php
// Start session and error reporting
if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get and validate project ID
$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

// Load dependencies
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get project details
$project = $project_id ? get_project_details($project_id) : null;

// Set page title
$page_title = $project ? 
    htmlspecialchars($project['title']) . " | Sunstore Industries" : 
    "Project Not Found | Sunstore Industries";

// Include header
require_once 'includes/header.php';
?>

<!-- Main Content Container -->
<main class="project-details-container">
    <?php if (!$project): ?>
        <!-- Project Not Found Message -->
        <section class="project-not-found">
            <div class="container py-5">
                <div class="alert alert-danger text-center">
                    <h2><i class="fas fa-exclamation-triangle me-2"></i>Project Not Found</h2>
                    <p class="lead">The requested project could not be located in our system.</p>
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <a href="projects.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Projects
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary">
                            <i class="fas fa-question-circle me-2"></i>Need Help?
                        </a>
                    </div>
                </div>
            </div>
        </section>

    <?php else: 
        // Safely extract project data with defaults
        $title = htmlspecialchars($project['title'] ?? 'Unnamed Project');
        $location = htmlspecialchars($project['location'] ?? 'Location not specified');
        $status = htmlspecialchars($project['status'] ?? 'Status unknown');
        $date = !empty($project['date']) ? date('F j, Y', strtotime($project['date'])) : 'Date not available';
        $description = nl2br(htmlspecialchars($project['description'] ?? 'No description available'));
        $main_image = !empty($project['image']) ? 
            'assets/projects/'.htmlspecialchars($project['image']) : 
            'assets/images/default-project.jpg';
        $video = $project['video'] ?? null;
        $size = htmlspecialchars($project['size'] ?? 'N/A');
        $annual_output = htmlspecialchars($project['annual_output'] ?? 'N/A');
        $co2_savings = htmlspecialchars($project['co2_savings'] ?? 'N/A');
        $type = htmlspecialchars($project['type'] ?? 'N/A');
        $specs = $project['specs'] ?? []; // Initialize specs array
        ?>
        
        <!-- Project Header Section -->
        <section class="project-header">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="projects.php">Our Projects</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= $title ?></li>
                    </ol>
                </nav>

                <div class="row align-items-end mb-4">
                    <div class="col-md-8">
                        <h1 class="project-title"><?= $title ?></h1>
                        <div class="project-meta d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-primary">
                                <i class="fas fa-map-marker-alt me-1"></i><?= $location ?>
                            </span>
                            <span class="badge bg-<?= 
                                $status === 'Completed' ? 'success' : 
                                ($status === 'Ongoing' ? 'warning' : 'info') 
                            ?>">
                                <?= $status ?>
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fas fa-calendar-alt me-1"></i><?= $date ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="contact.php?ref=project-<?= $project_id ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope me-2"></i>Get a Quote
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Project Content -->
        <section class="project-content">
            <div class="container">
                <div class="row">
                    <!-- Left Column - Main Content -->
                    <div class="col-lg-8">
                        <!-- Main Image -->
                        <div class="project-main-image card mb-4 border-0 shadow-sm">
                            <img src="<?= $main_image ?>" 
                                 class="card-img-top" 
                                 alt="<?= $title ?>" 
                                 loading="lazy">
                        </div>

                        <!-- Gallery Thumbnails -->
                        <?php if (!empty($project['gallery'])): ?>
                        <div class="project-gallery mb-5">
                            <h3 class="mb-3">Project Gallery</h3>
                            <div class="row g-3">
                                <?php foreach ($project['gallery'] as $index => $image): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="assets/projects/gallery/<?= htmlspecialchars($image) ?>" 
                                       class="gallery-item" 
                                       data-fancybox="gallery"
                                       data-caption="<?= $title ?> - Image <?= $index + 1 ?>">
                                        <img src="assets/projects/gallery/thumbs/<?= htmlspecialchars($image) ?>" 
                                             class="img-fluid rounded" 
                                             alt=""
                                             loading="lazy">
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Project Description -->
                        <div class="project-description card mb-4 border-0 shadow-sm">
                            <div class="card-body">
                                <h2 class="card-title">Project Overview</h2>
                                <div class="description-content">
                                    <?= $description ?>
                                </div>
                            </div>
                        </div>

                        <!-- Project Video -->
                        <?php if ($video): ?>
                        <div class="project-video card mb-4 border-0 shadow-sm">
                            <div class="card-body">
                                <h2 class="card-title">Project Video</h2>
                                <div class="ratio ratio-16x9">
                                    <iframe src="<?= htmlspecialchars($video) ?>" 
                                            allowfullscreen 
                                            loading="lazy"></iframe>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column - Sidebar -->
                    <div class="col-lg-4">
                        <!-- Specifications Card -->
                        <div class="project-specs card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h3 class="mb-0">Technical Specifications</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($specs)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($specs as $spec): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold"><?= htmlspecialchars($spec['name'] ?? '') ?></div>
                                            </div>
                                            <span class="text-primary"><?= htmlspecialchars($spec['value'] ?? '') ?></span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">No technical specifications available for this project.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- System Details Card -->
                        <div class="system-details card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h3 class="mb-0">System Performance</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="detail-item">
                                            <div class="detail-label">System Size</div>
                                            <div class="detail-value"><?= $size ?> kW</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="detail-item">
                                            <div class="detail-label">Type</div>
                                            <div class="detail-value"><?= $type ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="detail-item">
                                            <div class="detail-label">Annual Output</div>
                                            <div class="detail-value"><?= $annual_output ?> kWh</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="detail-item">
                                            <div class="detail-label">CO₂ Savings</div>
                                            <div class="detail-value"><?= $co2_savings ?> tons/yr</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonial -->
                        <?php if (!empty($project['testimonial'])): ?>
                        <div class="testimonial card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h3 class="mb-0">Client Testimonial</h3>
                            </div>
                            <div class="card-body">
                                <blockquote class="blockquote mb-0">
                                    <p class="testimonial-text">"<?= nl2br(htmlspecialchars($project['testimonial']['content'] ?? '')) ?>"</p>
                                    <footer class="blockquote-footer mt-3">
                                        <cite><?= htmlspecialchars($project['testimonial']['client'] ?? '') ?></cite>,
                                        <cite><?= htmlspecialchars($project['testimonial']['position'] ?? '') ?></cite>
                                    </footer>
                                </blockquote>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- CTA Card -->
                        <div class="cta-card card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h4 class="card-title">Interested in Solar?</h4>
                                <p class="card-text">Get a free consultation for your own solar project.</p>
                                <a href="contact.php?ref=project-<?= $project_id ?>" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-calendar-check me-2"></i>Schedule Consultation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Projects -->
        <?php $related_projects = get_related_projects($project_id, $project['category'] ?? '', 3); ?>
        <?php if (!empty($related_projects)): ?>
        <section class="related-projects py-5 bg-light">
            <div class="container">
                <h2 class="section-title mb-4 text-center">Similar Projects</h2>
                <div class="row g-4">
                    <?php foreach ($related_projects as $related): ?>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="assets/projects/<?= htmlspecialchars($related['image'] ?? '') ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($related['title'] ?? '') ?>"
                                 loading="lazy">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($related['title'] ?? '') ?></h5>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-primary"><?= htmlspecialchars($related['location'] ?? '') ?></span>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($related['category'] ?? '') ?></span>
                                </div>
                                <a href="project-details.php?id=<?= $related['id'] ?? '' ?>" class="btn btn-outline-primary w-100">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php
// Include footer
require_once 'includes/footer.php';
?>