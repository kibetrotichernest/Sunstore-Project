<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Add Project";
include '../includes/admin-header.php';

// Solar installation categories
$categories = [
    'Residential',
    'Commercial',
    'Industrial',
    'Agricultural',
    'Institutional',
    'Government',
    'Healthcare',
    'Educational',
    'Religious',
    'Hospitality',
    'Utility-Scale',
    'Off-Grid',
    'Hybrid Systems',
    'Water Pumping',
    'Street Lighting'
];

// All 47 counties of Kenya
$counties = [
    'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet',
    'Embu', 'Garissa', 'Homa Bay', 'Isiolo', 'Kajiado',
    'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga',
    'Kisii', 'Kisumu', 'Kitui', 'Kwale', 'Laikipia',
    'Lamu', 'Machakos', 'Makueni', 'Mandera', 'Marsabit',
    'Meru', 'Migori', 'Mombasa', 'Murang\'a', 'Nairobi',
    'Nakuru', 'Nandi', 'Narok', 'Nyamira', 'Nyandarua',
    'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River',
    'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu',
    'Vihiga', 'Wajir', 'West Pokot'
];
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Project</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Projects
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="save.php" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Project Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">System Size (kW)</label>
                                <input type="number" step="0.01" class="form-control" name="size" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>">
                                            <?= htmlspecialchars($category) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="Other">Other (Specify)</option>
                                </select>
                                <div id="otherCategoryContainer" style="display:none; margin-top:5px;">
                                    <input type="text" class="form-control" name="other_category" placeholder="Enter other category">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">County</label>
                                <select class="form-select" name="location" required>
                                    <option value="">Select County</option>
                                    <?php foreach ($counties as $county): ?>
                                        <option value="<?= htmlspecialchars($county) ?>">
                                            <?= htmlspecialchars($county) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="Completed">Completed</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Upcoming">Upcoming</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Rest of your form remains the same -->
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Completion Date</label>
                                <input type="date" class="form-control" name="date_completed" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Featured Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Project Specifications (One per line)</label>
                            <textarea class="form-control" name="specs" rows="5" placeholder="Enter each specification on a new line"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Video URL (Optional)</label>
                            <input type="url" class="form-control" name="video" placeholder="https://youtube.com/embed/...">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Project</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Show/hide other category field
document.querySelector('select[name="category"]').addEventListener('change', function() {
    const otherContainer = document.getElementById('otherCategoryContainer');
    otherContainer.style.display = this.value === 'Other' ? 'block' : 'none';
});
</script>

<?php include '../includes/admin-footer.php'; ?>