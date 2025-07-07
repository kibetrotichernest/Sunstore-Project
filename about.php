<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "About Sunstore Industries";
require_once 'includes/header.php';

// Fetch company data from database
try {
    $company_data = [];
    $conn = db_connect();
    
    // Get company stats
    $stats_stmt = $conn->prepare("SELECT * FROM company_stats LIMIT 1");
    $stats_stmt->execute();
    $company_stats = $stats_stmt->get_result()->fetch_assoc();
    
    // Get team members
    $team_stmt = $conn->prepare("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order");
    $team_stmt->execute();
    $team_members = $team_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get company milestones
    $milestones_stmt = $conn->prepare("SELECT * FROM company_milestones ORDER BY year ASC");
    $milestones_stmt->execute();
    $milestones = $milestones_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Error loading about page data: " . $e->getMessage());
    // Fallback data if database fails
    $company_stats = [
        'projects_completed' => 250,
        'counties_served' => 15,
        'total_capacity' => 1.2,
        'satisfaction_rate' => 98
    ];
    
    $team_members = [
        [
            'name' => 'James Mwangi',
            'position' => 'Founder & CEO',
            'bio' => '15+ years in renewable energy solutions',
            'image' => 'ceo.jpg'
        ],
        [
            'name' => 'Sarah Kamau',
            'position' => 'Chief Technology Officer',
            'bio' => 'Solar engineering specialist',
            'image' => 'cto.jpg'
        ],
        [
            'name' => 'David Ochieng',
            'position' => 'Director of Operations',
            'bio' => 'Project management expert',
            'image' => 'operations.jpg'
        ]
    ];
    
    $milestones = [
        ['year' => 2015, 'title' => 'Company Founded', 'description' => 'Started operations in Nairobi County'],
        ['year' => 2017, 'title' => 'Commercial Expansion', 'description' => 'Began serving business clients'],
        ['year' => 2020, 'title' => 'Regional Growth', 'description' => 'Expanded to Tanzania and Uganda'],
        ['year' => 2023, 'title' => '250+ Projects', 'description' => 'Celebrated 250th installation']
    ];
}
?>

<div class="about-us-container py-5">
    <div class="container">
        <!-- Hero Section -->
        <section class="about-hero mb-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary mb-4">Powering Kenya's Future with Solar Energy</h1>
                    <p class="lead mb-4">Sunstore Industries is revolutionizing renewable energy solutions across East Africa, providing sustainable power to homes, businesses, and communities.</p>
                    <div class="d-flex gap-3">
                        <a href="#our-mission" class="btn btn-primary btn-lg px-4">Our Mission</a>
                        <a href="#our-team" class="btn btn-outline-primary btn-lg px-4">Meet Our Team</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about-hero.jpg" alt="Solar panel installation team" class="img-fluid rounded shadow">
                </div>
            </div>
        </section>

        <!-- Our Story -->
        <section class="our-story mb-5 py-4 bg-light rounded-3">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="fw-bold mb-4">Our Humble Beginnings</h2>
                    <p class="mb-4">Founded in 2015 in Nairobi, Sunstore Industries started as a small team of three engineers passionate about bringing affordable solar solutions to Kenyan households. What began as rooftop installations in local communities has grown into one of East Africa's most trusted solar energy providers.</p>
                    <div class="timeline">
                        <?php foreach ($milestones as $milestone): ?>
                        <div class="timeline-item">
                            <div class="timeline-year"><?= htmlspecialchars($milestone['year']) ?></div>
                            <div class="timeline-content">
                                <h5><?= htmlspecialchars($milestone['title']) ?></h5>
                                <p><?= htmlspecialchars($milestone['description']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission & Vision -->
        <section id="our-mission" class="mission-vision mb-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="icon-box bg-primary-light mb-3">
                                <i class="fas fa-bullseye text-primary"></i>
                            </div>
                            <h3 class="fw-bold mb-3">Our Mission</h3>
                            <p>To make clean, reliable solar energy accessible and affordable for every Kenyan household and business, reducing energy poverty while promoting environmental sustainability.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="icon-box bg-primary-light mb-3">
                                <i class="fas fa-eye text-primary"></i>
                            </div>
                            <h3 class="fw-bold mb-3">Our Vision</h3>
                            <p>To be East Africa's leading solar energy solutions provider, powering 1 million homes and businesses with renewable energy by 2030.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section id="our-team" class="our-team mb-5">
            <h2 class="text-center fw-bold mb-5">Meet Our Leadership Team</h2>
            <div class="row g-4">
                <?php foreach ($team_members as $member): ?>
                <div class="col-md-4">
                    <div class="team-card text-center">
                        <img src="assets/images/team/<?= htmlspecialchars($member['image']) ?>" 
                             alt="<?= htmlspecialchars($member['name']) ?>" 
                             class="img-fluid rounded-circle mb-3">
                        <h4><?= htmlspecialchars($member['name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($member['position']) ?></p>
                        <p class="small"><?= htmlspecialchars($member['bio']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <p class="lead">+ 45 dedicated solar technicians, engineers, and support staff</p>
                <a href="careers.php" class="btn btn-outline-primary">Join Our Team</a>
            </div>
        </section>

        <!-- Achievements -->
        <section class="achievements mb-5 bg-primary text-white py-5 rounded-3">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold"><?= htmlspecialchars($company_stats['projects_completed'] ?? 250) ?>+</h3>
                    <p>Completed Projects</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold"><?= htmlspecialchars($company_stats['counties_served'] ?? 15) ?></h3>
                    <p>Counties Served</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold"><?= htmlspecialchars($company_stats['total_capacity'] ?? 1.2) ?>MW</h3>
                    <p>Total Installed Capacity</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold"><?= htmlspecialchars($company_stats['satisfaction_rate'] ?? 98) ?>%</h3>
                    <p>Customer Satisfaction</p>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="cta text-center py-5">
            <h2 class="fw-bold mb-4">Ready to Harness Solar Power?</h2>
            <p class="lead mb-4">Join Kenya's renewable energy revolution with Sunstore Industries</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="contact.php" class="btn btn-primary btn-lg px-4">Get a Free Consultation</a>
                <a href="projects.php" class="btn btn-outline-primary btn-lg px-4">View Our Projects</a>
            </div>
        </section>
    </div>
</div>

<!-- Include the same CSS styles as before -->
<style>
    .about-us-container {
        line-height: 1.6;
    }
    .timeline {
        position: relative;
        padding-left: 50px;
        margin-top: 30px;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 30px;
    }
    .timeline-year {
        position: absolute;
        left: -50px;
        top: 0;
        background: #0d6efd;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    .timeline-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .value-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }
    .team-card img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border: 3px solid #0d6efd;
    }
    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1);
    }
</style>

<?php 
require_once 'includes/footer.php';
?>